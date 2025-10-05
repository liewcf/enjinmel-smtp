<?php
/**
 * EnjinMel SMTP Settings Page
 *
 * Add the settings fields for REST configuration.
 *
 * @package EnjinMel_SMTP
 */

/**
 * EnjinMel SMTP Settings Page Class
 */
class EnjinMel_SMTP_Settings_Page {

	/**
	 * Add the settings fields for REST configuration.
	 */
	public static function add_settings_fields() {
		$group   = enjinmel_smtp_settings_group();
		$section = 'enjinmel_smtp_section';
		add_settings_field(
			'enjinmel_smtp_api_key',
			__( 'Enginemailer API Key', 'enjinmel-smtp' ),
			array( __CLASS__, 'api_key_render' ),
			$group,
			$section
		);

		add_settings_field(
			'enjinmel_smtp_from_name',
			__( 'Sender Name', 'enjinmel-smtp' ),
			array( __CLASS__, 'from_name_render' ),
			$group,
			$section
		);

		add_settings_field(
			'enjinmel_smtp_from_email',
			__( 'Sender Email', 'enjinmel-smtp' ),
			array( __CLASS__, 'from_email_render' ),
			$group,
			$section
		);

		add_settings_field(
			'enjinmel_smtp_template_id',
			__( 'Template ID (optional)', 'enjinmel-smtp' ),
			array( __CLASS__, 'template_id_render' ),
			$group,
			$section
		);

		add_settings_field(
			'enjinmel_smtp_campaign_name',
			__( 'Campaign Name (optional)', 'enjinmel-smtp' ),
			array( __CLASS__, 'campaign_name_render' ),
			$group,
			$section
		);

		add_settings_field(
			'enjinmel_smtp_force_from',
			__( 'Force Sender', 'enjinmel-smtp' ),
			array( __CLASS__, 'force_from_render' ),
			$group,
			$section
		);

		add_settings_field(
			'enjinmel_smtp_enable_logging',
			__( 'Enable Logging', 'enjinmel-smtp' ),
			array( __CLASS__, 'enable_logging_render' ),
			$group,
			$section
		);
	}

	/**
	 * Render the API key field.
	 */
	public static function api_key_render() {
		$options = enjinmel_smtp_get_settings();
		$display_value = '';
		$placeholder = __( 'Enter API key', 'enjinmel-smtp' );

		if ( ! empty( $options['api_key'] ) ) {
			$decrypted = EnjinMel_SMTP_Encryption::decrypt( $options['api_key'] );
			if ( ! is_wp_error( $decrypted ) && '' !== $decrypted ) {
				$display_value = self::mask_api_key( $decrypted );
				$placeholder = '';
			}
		}
		?>
		<input type='text' autocomplete='off' name='enjinmel_smtp_settings[api_key]' value='<?php echo esc_attr( $display_value ); ?>' placeholder='<?php echo esc_attr( $placeholder ); ?>'>
		<?php
	}

	/**
	 * Mask an API key for display.
	 *
	 * @param string $api_key The API key to mask.
	 * @return string
	 */
	private static function mask_api_key( $api_key ) {
		$key_length = strlen( $api_key );
		if ( $key_length <= 4 ) {
			return str_repeat( '*', $key_length );
		}
		$visible_chars = 4;
		$masked_length = $key_length - $visible_chars;
		return substr( $api_key, 0, $visible_chars ) . str_repeat( '*', min( $masked_length, 20 ) );
	}

	/**
	 * Render the campaign name field.
	 */
	public static function campaign_name_render() {
		$options = enjinmel_smtp_get_settings();
		?>
		<input type='text' name='enjinmel_smtp_settings[campaign_name]' value='<?php echo isset( $options['campaign_name'] ) ? esc_attr( $options['campaign_name'] ) : ''; ?>' placeholder='<?php echo esc_attr__( 'Transactional Campaign', 'enjinmel-smtp' ); ?>'>
		<?php
	}

	/**
	 * Render the template ID field.
	 */
	public static function template_id_render() {
		$options = enjinmel_smtp_get_settings();
		?>
		<input type='text' name='enjinmel_smtp_settings[template_id]' value='<?php echo isset( $options['template_id'] ) ? esc_attr( $options['template_id'] ) : ''; ?>' placeholder='<?php echo esc_attr__( 'e.g. TPL-123', 'enjinmel-smtp' ); ?>'>
		<?php
	}

	/**
	 * Render the sender name field.
	 */
	public static function from_name_render() {
		$options = enjinmel_smtp_get_settings();
		?>
		<input type='text' name='enjinmel_smtp_settings[from_name]' value='<?php echo isset( $options['from_name'] ) ? esc_attr( $options['from_name'] ) : ''; ?>' placeholder='<?php echo esc_attr__( 'EnjinMel', 'enjinmel-smtp' ); ?>'>
		<?php
	}

	/**
	 * Render the sender email field.
	 */
	public static function from_email_render() {
		$options = enjinmel_smtp_get_settings();
		?>
		<input type='email' name='enjinmel_smtp_settings[from_email]' value='<?php echo isset( $options['from_email'] ) ? esc_attr( $options['from_email'] ) : ''; ?>' placeholder='noreply@example.com'>
		<?php
	}

	/**
	 * Render the force sender checkbox.
	 */
	public static function force_from_render() {
		$options = enjinmel_smtp_get_settings();
		$checked = isset( $options['force_from'] ) ? (int) $options['force_from'] : 0;
		?>
		<label>
			<input type='checkbox' name='enjinmel_smtp_settings[force_from]' value='1' <?php checked( $checked, 1 ); ?>>
			<?php echo esc_html__( 'Always use the configured sender details', 'enjinmel-smtp' ); ?>
		</label>
		<?php
	}

	/**
	 * Render the enable logging checkbox.
	 */
	public static function enable_logging_render() {
		$options = enjinmel_smtp_get_settings();
		$checked = isset( $options['enable_logging'] ) ? (int) $options['enable_logging'] : 1;
		?>
		<label>
			<input type='checkbox' name='enjinmel_smtp_settings[enable_logging]' value='1' <?php checked( $checked, 1 ); ?>>
			<?php echo esc_html__( 'Store send results in the EnjinMel log table', 'enjinmel-smtp' ); ?>
		</label>
		<?php
	}

	/**
	 * AJAX handler for Send Test Email (admin only).
	 */
	public static function send_test_email() {
		check_ajax_referer( 'enjinmel_smtp_send_test_email', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'enjinmel-smtp' ) ), 403 );
		}
		$to = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
		if ( empty( $to ) || ! is_email( $to ) ) {
			wp_send_json_error( array( 'message' => __( 'Please provide a valid email address.', 'enjinmel-smtp' ) ), 400 );
		}

		$subject = __( 'EnjinMel SMTP Test Email', 'enjinmel-smtp' );
		$message = __( 'This is a test email sent from the EnjinMel SMTP plugin settings page.', 'enjinmel-smtp' );
		$sent    = wp_mail( $to, $subject, $message );

		if ( is_wp_error( $sent ) ) {
			wp_send_json_error( array( 'message' => $sent->get_error_message() ), 500 );
		}

		if ( true === $sent ) {
			wp_send_json_success( array( 'message' => __( 'Email sent successfully.', 'enjinmel-smtp' ) ) );
		}

		wp_send_json_error( array( 'message' => __( 'EnjinMel API did not confirm the send.', 'enjinmel-smtp' ) ), 500 );
	}
}
add_action( 'admin_init', array( 'EnjinMel_SMTP_Settings_Page', 'add_settings_fields' ) );
add_action( 'wp_ajax_enjinmel_smtp_send_test_email', array( 'EnjinMel_SMTP_Settings_Page', 'send_test_email' ) );
