<?php

/**
 * EngineMail SMTP - Logging Class
 *
 * Logs sent and failed emails to the custom table created on activation.
 * Table: {$wpdb->prefix}enginemail_smtp_logs
 */
class EngineMail_SMTP_Logging {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'wp_mail_succeeded', array( __CLASS__, 'on_mail_succeeded' ), 10, 1 );
        add_action( 'wp_mail_failed', array( __CLASS__, 'on_mail_failed' ), 10, 1 );
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

        $message = is_object( $wp_error ) ? $wp_error->get_error_message() : __( 'Unknown error.', 'enginemail-smtp' );
        $message = is_string( $message ) ? $message : ''; // ensure string

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

        $table = $wpdb->prefix . 'enginemail_smtp_logs';

        // Ensure bounds for VARCHAR columns.
        $to_emails     = self::truncate( $to_emails, 255 );
        $subject       = self::truncate( $subject, 255 );
        $error_message = (string) $error_message; // TEXT

        $wpdb->insert(
            $table,
            array(
                'timestamp'     => current_time( 'mysql' ),
                'to_email'      => $to_emails,
                'subject'       => $subject,
                'status'        => ( 'failed' === $status ) ? 'failed' : 'sent',
                'error_message' => $error_message,
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Check if logging is enabled. Defaults to true when unset.
     *
     * @return bool
     */
    private static function is_enabled() {
        $opts = get_option( 'enginemail_smtp_settings', array() );
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
}

