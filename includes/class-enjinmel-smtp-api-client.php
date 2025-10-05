<?php
/**
 * EnjinMel SMTP API client helpers.
 *
 * @package EnjinMel_SMTP
 */

/**
 * EnjinMel API client for transactional email submissions.
 */
class EnjinMel_SMTP_API_Client {

	private const ENDPOINT             = 'https://api.enginemailer.com/RESTAPI/V2/Submission/SendEmail';
	private const MAX_ATTACHMENT_BYTES = 5242880; // 5MB per current API limits.

	/**
	 * Submit an email payload to the EnjinMel REST endpoint.
	 *
	 * @param array $args WP mail arguments.
	 * @return array|WP_Error Response data array on success, WP_Error on failure.
	 */
	public static function send( array $args ) {
		$settings = enjinmel_smtp_get_settings( array() );

		$api_key = self::maybe_decrypt_api_key( $settings );
		if ( is_wp_error( $api_key ) ) {
			return $api_key;
		}

		$normalized = self::normalize_mail_args( $args );
		if ( is_wp_error( $normalized ) ) {
			return $normalized;
		}

		$payload = self::build_payload( $normalized, $settings );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		/**
		 * Allow last-minute mutation of the outbound payload before submission.
		 *
		 * @param array $payload   The API payload.
		 * @param array $normalized Normalized mail arguments.
		 * @param array $settings  Plugin settings.
		 */
		$payload = apply_filters( 'enjinmel_smtp_payload', $payload, $normalized, $settings );

		/**
		 * Fires immediately before the EnjinMel REST request is dispatched.
		 *
		 * @param array $normalized Normalized mail arguments.
		 * @param array $payload    Request payload that will be submitted.
		 */
		do_action( 'enjinmel_smtp_before_send', $normalized, $payload );

		$timeout = apply_filters( 'enjinmel_smtp_request_timeout', 15, $normalized, $payload, $settings );

		$request_args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'APIKey'       => $api_key,
			),
			'body'    => wp_json_encode( $payload ),
			'timeout' => $timeout,
		);

		/**
		 * Filter the request arguments prior to dispatch.
		 *
		 * @param array $request_args HTTP API arguments.
		 * @param array $payload      EnjinMel payload.
		 */
		$request_args = apply_filters( 'enjinmel_smtp_request_args', $request_args, $payload, $normalized, $settings );

		$response = wp_remote_post( self::ENDPOINT, $request_args );
		if ( is_wp_error( $response ) ) {
			$result = new WP_Error(
				'enjinmel_http_error',
				__( 'Unable to reach the EnjinMel API.', 'enjinmel-smtp' ),
				array( 'error' => $response )
			);
			do_action( 'enjinmel_smtp_after_send', $normalized, $payload, $result );
			return $result;
		}

		$code     = wp_remote_retrieve_response_code( $response );
		$body_raw = wp_remote_retrieve_body( $response );
		$body     = json_decode( $body_raw, true );

		if ( 200 !== (int) $code ) {
			$result = new WP_Error(
				'enjinmel_http_status',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'EnjinMel API returned HTTP %d.', 'enjinmel-smtp' ),
					(int) $code
				),
				array(
					'code' => $code,
					'body' => $body_raw,
				)
			);
			do_action( 'enjinmel_smtp_after_send', $normalized, $payload, $result );
			return $result;
		}

		if ( ! is_array( $body ) ) {
			$result = new WP_Error(
				'enjinmel_invalid_response',
				__( 'Unexpected EnjinMel API response.', 'enjinmel-smtp' ),
				array( 'body' => $body_raw )
			);
			do_action( 'enjinmel_smtp_after_send', $normalized, $payload, $result );
			return $result;
		}

		$result = isset( $body['Result'] ) ? $body['Result'] : $body;
		$status = isset( $result['StatusCode'] ) ? (string) $result['StatusCode'] : '';

		if ( '200' !== $status && 'OK' !== strtoupper( $status ) ) {
			$message      = isset( $result['Message'] ) ? $result['Message'] : __( 'Unknown error.', 'enjinmel-smtp' );
			$result_error = new WP_Error( 'enjinmel_api_error', $message, array( 'response' => $body ) );
			do_action( 'enjinmel_smtp_after_send', $normalized, $payload, $result_error );
			return $result_error;
		}

		do_action( 'enjinmel_smtp_after_send', $normalized, $payload, $body );
		return $body;
	}

	/**
	 * Retrieve and decrypt the stored API key.
	 *
	 * @param array $settings Plugin settings array.
	 * @return string|WP_Error
	 */
	private static function maybe_decrypt_api_key( array $settings ) {
		if ( empty( $settings['api_key'] ) ) {
			return new WP_Error( 'enjinmel_missing_api_key', __( 'EnjinMel API key is not configured.', 'enjinmel-smtp' ) );
		}

		$decrypted = EnjinMel_SMTP_Encryption::decrypt( $settings['api_key'] );
		if ( is_wp_error( $decrypted ) ) {
			return $decrypted;
		}

		if ( empty( $decrypted ) ) {
			return new WP_Error( 'enjinmel_invalid_api_key', __( 'EnjinMel API key could not be decrypted.', 'enjinmel-smtp' ) );
		}

		return (string) $decrypted;
	}

	/**
	 * Normalize the wp_mail arguments.
	 *
	 * @param array $args Raw wp_mail arguments.
	 * @return array|WP_Error Normalized args or error when validation fails.
	 */
	private static function normalize_mail_args( array $args ) {
		$defaults = array(
			'to'          => array(),
			'subject'     => '',
			'message'     => '',
			'headers'     => array(),
			'attachments' => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		$recipients = self::parse_addresses( $args['to'] );
		if ( empty( $recipients ) ) {
			return new WP_Error( 'enjinmel_missing_recipient', __( 'Email recipient is required.', 'enjinmel-smtp' ) );
		}

		$headers = self::parse_headers( $args['headers'] );

		$content_type = isset( $headers['content_type'] ) && '' !== $headers['content_type'] ? $headers['content_type'] : apply_filters( 'wp_mail_content_type', 'text/plain' );
		$content_type = is_string( $content_type ) ? trim( strtolower( $content_type ) ) : 'text/plain';

		$attachments = self::normalize_attachments( $args['attachments'] );
		if ( is_wp_error( $attachments ) ) {
			return $attachments;
		}

		return array(
			'to'           => $recipients,
			'subject'      => (string) $args['subject'],
			'message'      => (string) $args['message'],
			'headers'      => $headers,
			'content_type' => $content_type,
			'attachments'  => $attachments,
		);
	}

	/**
	 * Build the API payload.
	 *
	 * @param array $normalized Normalized mail arguments.
	 * @param array $settings   Plugin settings.
	 * @return array|WP_Error
	 */
	private static function build_payload( array $normalized, array $settings ) {
		$force_from       = ! empty( $settings['force_from'] );
		$default_sender   = isset( $settings['from_email'] ) ? sanitize_email( $settings['from_email'] ) : '';
		$default_name     = isset( $settings['from_name'] ) ? sanitize_text_field( $settings['from_name'] ) : '';
		$default_campaign = isset( $settings['campaign_name'] ) ? sanitize_text_field( $settings['campaign_name'] ) : '';
		$template_id      = isset( $settings['template_id'] ) ? sanitize_text_field( $settings['template_id'] ) : '';

		$from = $normalized['headers']['from'];
		if ( $force_from || empty( $from['email'] ) ) {
			$from['email'] = $default_sender;
			$from['name']  = $default_name;
		}

		if ( empty( $from['email'] ) || ! is_email( $from['email'] ) ) {
			return new WP_Error( 'enjinmel_missing_sender', __( 'A valid sender email must be configured.', 'enjinmel-smtp' ) );
		}

		$to_emails = implode( ',', $normalized['to'] );

		$is_html = false !== strpos( $normalized['content_type'], 'html' );

		$payload = array(
			'ToEmail'              => $to_emails,
			'Subject'              => $normalized['subject'],
			'SenderEmail'          => $from['email'],
			'SenderName'           => $from['name'],
			'SubmittedContent'     => $normalized['message'],
			'SubmittedContentType' => $normalized['content_type'],
			'IsHtmlContent'        => $is_html,
		);

		if ( ! empty( $default_campaign ) ) {
			$payload['CampaignName'] = $default_campaign;
		}

		if ( ! empty( $template_id ) ) {
			$payload['TemplateId'] = $template_id;
		}

		if ( ! empty( $normalized['attachments'] ) ) {
			$payload['Attachments'] = $normalized['attachments'];
		}

		if ( ! empty( $normalized['headers']['cc'] ) ) {
			$payload['CCEmails'] = array_values( $normalized['headers']['cc'] );
		}

		if ( ! empty( $normalized['headers']['bcc'] ) ) {
			$payload['BCCEmails'] = array_values( $normalized['headers']['bcc'] );
		}

		if ( ! empty( $normalized['headers']['reply_to'] ) ) {
			$payload['ReplyToEmail'] = $normalized['headers']['reply_to'][0];
		}

		return $payload;
	}

	/**
	 * Normalize attachments into the shape required by EnjinMel.
	 *
	 * @param array $attachments List of attachment paths.
	 * @return array|WP_Error
	 */
	private static function normalize_attachments( $attachments ) {
		if ( empty( $attachments ) ) {
			return array();
		}

		if ( ! is_array( $attachments ) ) {
			$attachments = array( $attachments );
		}

		$normalized = array();

		foreach ( $attachments as $attachment ) {
			if ( is_array( $attachment ) && isset( $attachment['name'], $attachment['data'] ) ) {
				$raw      = (string) $attachment['data'];
				$raw_size = strlen( $raw );
				if ( $raw_size > self::MAX_ATTACHMENT_BYTES ) {
					return new WP_Error(
						'enjinmel_attachment_too_large',
						sprintf(
							/* translators: %s: file name */
							__( 'Attachment %s exceeds the 5MB EnjinMel API limit.', 'enjinmel-smtp' ),
							sanitize_file_name( $attachment['name'] )
						),
						array(
							'file' => $attachment['name'],
							'size' => $raw_size,
						)
					);
				}

					$normalized[] = array(
						'Filename' => sanitize_file_name( $attachment['name'] ),
						'Content'  => base64_encode( $raw ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- API payload requires Base64 encoded attachments.
					);
					continue;
			}

			$path = realpath( $attachment );
			if ( false === $path || ! file_exists( $path ) ) {
				return new WP_Error( 'enjinmel_missing_attachment', __( 'Attachment file not found.', 'enjinmel-smtp' ), array( 'file' => $attachment ) );
			}

			$size = filesize( $path );
			if ( false !== $size && $size > self::MAX_ATTACHMENT_BYTES ) {
				return new WP_Error(
					'enjinmel_attachment_too_large',
					sprintf(
						/* translators: %s: file name */
						__( 'Attachment %s exceeds the 5MB EnjinMel API limit.', 'enjinmel-smtp' ),
						wp_basename( $path )
					),
					array(
						'file' => $attachment,
						'size' => $size,
					)
				);
			}

				$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local attachment from disk.
			if ( false === $contents ) {
				return new WP_Error( 'enjinmel_unreadable_attachment', __( 'Unable to read attachment.', 'enjinmel-smtp' ), array( 'file' => $attachment ) );
			}

				$normalized[] = array(
					'Filename' => sanitize_file_name( wp_basename( $path ) ),
					'Content'  => base64_encode( $contents ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- API payload requires Base64 encoded attachments.
				);
		}

		return $normalized;
	}

	/**
	 * Parse recipients into a clean array of email addresses.
	 *
	 * @param string|array $addresses Recipient or list of recipients.
	 * @return array
	 */
	private static function parse_addresses( $addresses ) {
		if ( empty( $addresses ) ) {
			return array();
		}

		if ( is_string( $addresses ) ) {
			$addresses = wp_parse_list( $addresses );
		}

		$emails = array();
		foreach ( (array) $addresses as $address ) {
			$parsed = self::parse_address( $address );
			if ( $parsed && is_email( $parsed['email'] ) ) {
				$emails[] = $parsed['email'];
			}
		}

		return array_unique( $emails );
	}

	/**
	 * Parse and normalize wp_mail headers.
	 *
	 * @param string|array $headers Headers from wp_mail.
	 * @return array
	 */
	private static function parse_headers( $headers ) {
		$normalized = array(
			'from'         => array(
				'name'  => '',
				'email' => '',
			),
			'cc'           => array(),
			'bcc'          => array(),
			'reply_to'     => array(),
			'other'        => array(),
			'content_type' => '',
		);

		if ( empty( $headers ) ) {
			return $normalized;
		}

		if ( ! is_array( $headers ) ) {
			$headers = explode( "\n", str_replace( "\r\n", "\n", (string) $headers ) );
		}

		foreach ( $headers as $header ) {
			if ( empty( $header ) || false === strpos( $header, ':' ) ) {
				continue;
			}

			list( $name, $content ) = explode( ':', trim( $header ), 2 );
			$name                   = strtolower( trim( $name ) );
			$content                = trim( $content );

			switch ( $name ) {
				case 'from':
					$normalized['from'] = self::parse_address( $content );
					break;
				case 'cc':
					$normalized['cc'] = array_merge( $normalized['cc'], self::parse_address_list( $content ) );
					break;
				case 'bcc':
					$normalized['bcc'] = array_merge( $normalized['bcc'], self::parse_address_list( $content ) );
					break;
				case 'reply-to':
					$normalized['reply_to'] = array_merge( $normalized['reply_to'], self::parse_address_list( $content ) );
					break;
				case 'content-type':
					$normalized['content_type'] = strtolower( $content );
					break;
				default:
					$normalized['other'][ $name ] = $content;
					break;
			}
		}

		$normalized['cc']  = array_unique( $normalized['cc'] );
		$normalized['bcc'] = array_unique( $normalized['bcc'] );

		return $normalized;
	}

	/**
	 * Parse a header list into email strings.
	 *
	 * @param string $header_list Header list value.
	 * @return array
	 */
	private static function parse_address_list( $header_list ) {
		$emails = array();
		foreach ( wp_parse_list( $header_list ) as $item ) {
			$parsed = self::parse_address( $item );
			if ( $parsed && is_email( $parsed['email'] ) ) {
				$emails[] = $parsed['email'];
			}
		}
		return $emails;
	}

	/**
	 * Parse an address string into name/email components.
	 *
	 * @param string $address Raw address line.
	 * @return array
	 */
	private static function parse_address( $address ) {
		$address = trim( $address );
		if ( empty( $address ) ) {
			return array(
				'name'  => '',
				'email' => '',
			);
		}

		if ( preg_match( '/(.*)<(.+)>/', $address, $matches ) ) {
			$name  = sanitize_text_field( trim( $matches[1], " \"'" ) );
			$email = sanitize_email( trim( $matches[2] ) );
			return array(
				'name'  => $name,
				'email' => $email,
			);
		}

		return array(
			'name'  => '',
			'email' => sanitize_email( $address ),
		);
	}
}
