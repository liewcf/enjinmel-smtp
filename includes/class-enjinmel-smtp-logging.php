<?php
/**
 * EnjinMel SMTP - Logging Class
 *
 * Logs sent and failed emails to the custom table created on activation.
 * Table: {$wpdb->prefix}enjinmel_smtp_logs
 *
 * @package EnjinMel_SMTP
 */
class EnjinMel_SMTP_Logging {

	private const CRON_HOOK        = 'enjinmel_smtp_retention_daily';
	private const LEGACY_CRON_HOOK = 'enginemail_smtp_purge_logs';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'wp_mail_succeeded', array( __CLASS__, 'on_mail_succeeded' ), 10, 1 );
		add_action( 'wp_mail_failed', array( __CLASS__, 'on_mail_failed' ), 10, 1 );
		add_action( self::CRON_HOOK, array( __CLASS__, 'purge_logs' ) );
		add_action( self::LEGACY_CRON_HOOK, array( __CLASS__, 'purge_logs' ) );

		self::schedule_events();
	}

	/**
	 * Handle successful mail sends.
	 *
	 * @param array $mail_data { to, subject, message, headers, attachments }.
	 * @return void
	 */
	public static function on_mail_succeeded( $mail_data ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		$to_emails = self::normalize_recipients( isset( $mail_data['to'] ) ? $mail_data['to'] : '' );
		$subject   = self::normalize_subject( isset( $mail_data['subject'] ) ? $mail_data['subject'] : '' );

		self::insert_log( 'sent', $to_emails, $subject, '' );
	}

	/**
	 * Handle failed mail sends.
	 *
	 * @param WP_Error $wp_error Error object from wp_mail.
	 * @return void
	 */
	public static function on_mail_failed( $wp_error ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		$data      = is_object( $wp_error ) ? $wp_error->get_error_data( 'wp_mail_failed' ) : array();
		$to        = isset( $data['to'] ) ? $data['to'] : '';
		$subject_v = isset( $data['subject'] ) ? $data['subject'] : '';
		$to_emails = self::normalize_recipients( $to );
		$subject   = self::normalize_subject( $subject_v );

		$message = is_object( $wp_error ) ? $wp_error->get_error_message() : __( 'Unknown error.', 'enjinmel-smtp' );
		$message = is_string( $message ) ? $message : ''; // Ensure string.

		self::insert_log( 'failed', $to_emails, $subject, $message );
	}

	/**
	 * Insert a log row.
	 *
	 * @param string $status        'sent' or 'failed'.
	 * @param string $to_emails     Comma-separated recipients.
	 * @param string $subject       Email subject.
	 * @param string $error_message Error message if failed.
	 * @return void
	 */
	private static function insert_log( $status, $to_emails, $subject, $error_message ) {
		global $wpdb;

		$table = enjinmel_smtp_active_log_table();

		// Ensure bounds for VARCHAR columns.
		$to_emails     = self::truncate( $to_emails, 255 );
		$subject       = self::truncate( $subject, 255 );
		$error_message = (string) $error_message; // TEXT column expects string.

		$entry = array(
			'timestamp'     => current_time( 'mysql' ),
			'to_email'      => $to_emails,
			'subject'       => $subject,
			'status'        => ( 'failed' === $status ) ? 'failed' : 'sent',
			'error_message' => $error_message,
		);

		/**
		 * Filter a log entry prior to database insert.
		 *
		 * @param array  $entry         Associative array with timestamp, to_email, subject, status, error_message.
		 * @param string $status        Original status determined by the logger.
		 * @param string $to_emails     Normalized recipient list.
		 * @param string $subject       Normalized subject line.
		 * @param string $error_message Error message string (empty when not applicable).
		 */
		$entry = apply_filters( 'enjinmel_smtp_log_entry', $entry, $status, $to_emails, $subject, $error_message );
		$entry = apply_filters( 'enginemail_smtp_log_entry', $entry, $status, $to_emails, $subject, $error_message );

		if ( ! is_array( $entry ) ) {
			return;
		}

		$entry = self::normalize_log_entry( $entry );

		$wpdb->insert(
			$table,
			$entry,
			array( '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Check if logging is enabled. Defaults to true when unset.
	 *
	 * @return bool
	 */
	private static function is_enabled() {
		$opts = enjinmel_smtp_get_settings( array() );
		if ( ! is_array( $opts ) ) {
			return true;
		}
		// Default to enabled when option is absent.
		return array_key_exists( 'enable_logging', $opts ) ? (bool) $opts['enable_logging'] : true;
	}

	/**
	 * Normalize recipients to comma-separated sanitized emails.
	 *
	 * @param string|array $to Recipients.
	 * @return string
	 */
	private static function normalize_recipients( $to ) {
		$emails = array();
		if ( is_array( $to ) ) {
			foreach ( $to as $addr ) {
				$e = sanitize_email( $addr );
				if ( is_email( $e ) ) {
					$emails[] = $e;
				}
			}
		} else {
			// Split on commas if a string list is provided.
			$parts = array_map( 'trim', explode( ',', (string) $to ) );
			foreach ( $parts as $addr ) {
				if ( '' === $addr ) {
					continue;
				}
				$e = sanitize_email( $addr );
				if ( is_email( $e ) ) {
					$emails[] = $e;
				}
			}
		}
		return implode( ',', $emails );
	}

	/**
	 * Sanitize and bound the subject.
	 *
	 * @param string $subject Subject.
	 * @return string
	 */
	private static function normalize_subject( $subject ) {
		$subject = sanitize_text_field( (string) $subject );
		return self::truncate( $subject, 255 );
	}

	/**
	 * Truncate a string to a maximum length in a multibyte-safe manner when possible.
	 *
	 * @param string $text  Input text.
	 * @param int    $limit Max length.
	 * @return string
	 */
	private static function truncate( $text, $limit ) {
		$text = (string) $text;
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $text, 0, (int) $limit );
		}
		return substr( $text, 0, (int) $limit );
	}

	/**
	 * Normalize data before persisting a log entry.
	 *
	 * @param array $entry Log entry.
	 * @return array
	 */
	private static function normalize_log_entry( array $entry ) {
		$entry['timestamp'] = isset( $entry['timestamp'] ) ? $entry['timestamp'] : current_time( 'mysql' );
		$entry['to_email']  = isset( $entry['to_email'] ) ? self::truncate( (string) $entry['to_email'], 255 ) : '';
		$entry['subject']   = isset( $entry['subject'] ) ? self::truncate( sanitize_text_field( (string) $entry['subject'] ), 255 ) : '';

		$status          = isset( $entry['status'] ) ? strtolower( (string) $entry['status'] ) : 'sent';
		$entry['status'] = ( 'failed' === $status ) ? 'failed' : 'sent';

		$entry['error_message'] = isset( $entry['error_message'] ) ? (string) $entry['error_message'] : '';

		return array(
			'timestamp'     => $entry['timestamp'],
			'to_email'      => $entry['to_email'],
			'subject'       => $entry['subject'],
			'status'        => $entry['status'],
			'error_message' => $entry['error_message'],
		);
	}

	/**
	 * Schedule the retention purge as a daily cron event.
	 *
	 * @return void
	 */
	private static function schedule_purge_event() {
		if ( ! function_exists( 'wp_next_scheduled' ) || ! function_exists( 'wp_schedule_event' ) ) {
			return;
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Public wrapper used by activation routines to ensure events are scheduled.
	 *
	 * @return void
	 */
	public static function schedule_events() {
		self::unschedule_events();
		self::schedule_purge_event();
	}

	/**
	 * Unschedule the daily purge event.
	 *
	 * @return void
	 */
	public static function unschedule_events() {
		if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
			wp_clear_scheduled_hook( self::LEGACY_CRON_HOOK );
		}
	}

	/**
	 * Purge logs based on configured retention limits.
	 *
	 * @return void
	 */
	public static function purge_logs() {
		$tables = array_unique(
			array(
				enjinmel_smtp_log_table_name(),
				enjinmel_smtp_legacy_log_table_name(),
			)
		);

		$tables = array_filter(
			array_map( 'enjinmel_smtp_sanitize_table_name', $tables )
		);

		$days = (int) apply_filters( 'enjinmel_smtp_retention_days', 90 );
		$days = (int) apply_filters( 'enginemail_smtp_retention_days', $days );

		$max_rows = (int) apply_filters( 'enjinmel_smtp_retention_max_rows', 10000 );
		$max_rows = (int) apply_filters( 'enginemail_smtp_retention_max_rows', $max_rows );

		foreach ( $tables as $table ) {
			self::purge_table( $table, $days, $max_rows );
		}
	}

	/**
	 * Purge a specific log table according to retention rules.
	 *
	 * @param string $table    Table name.
	 * @param int    $days     Day-based retention limit.
	 * @param int    $max_rows Maximum number of rows to keep.
	 * @return void
	 */
	private static function purge_table( $table, $days, $max_rows ) {
		global $wpdb;

		if ( ! enjinmel_smtp_table_exists( $table ) ) {
			return;
		}

		if ( $days > 0 ) {
			$threshold_timestamp = strtotime( sprintf( '-%d days', $days ), current_time( 'timestamp' ) );
			if ( false !== $threshold_timestamp ) {
				$threshold = function_exists( 'wp_date' )
					? wp_date( 'Y-m-d H:i:s', $threshold_timestamp )
					: gmdate( 'Y-m-d H:i:s', $threshold_timestamp + ( get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE timestamp < %s", $threshold ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery -- Table name sanitized prior to interpolation.
			}
		}

		if ( $max_rows > 0 ) {
			$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery -- Table name sanitized prior to interpolation.
			if ( $count > $max_rows ) {
				$over_limit = $count - $max_rows;
				$ids        = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} ORDER BY timestamp ASC LIMIT %d", $over_limit ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery -- Table name sanitized prior to interpolation.
				if ( ! empty( $ids ) ) {
					$ids          = array_map( 'intval', $ids );
					$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", $ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table name sanitized prior to interpolation; placeholders generated explicitly for ids.
				}
			}
		}
	}
}
