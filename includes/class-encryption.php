<?php
/**
 * Lightweight wrapper around OpenSSL for option encryption.
 *
 * @package EngineMail SMTP
 */

class EngineMail_SMTP_Encryption {

    private const ENCRYPTION_METHOD = 'AES-256-CBC';

    /**
     * Encrypt data using the configured key material.
     *
     * @param string $data Plain text to encrypt.
     * @return string|WP_Error Base64 encoded cipher text or WP_Error on failure.
     */
    public static function encrypt( $data ) {
        if ( '' === $data ) {
            return '';
        }

        $creds = self::get_credentials();
        if ( is_wp_error( $creds ) ) {
            return $creds;
        }

        list( $key, $iv ) = $creds;

        $cipher = openssl_encrypt( $data, self::ENCRYPTION_METHOD, $key, 0, $iv );
        if ( false === $cipher ) {
            return new WP_Error( 'enginemail_encryption_failed', __( 'Unable to encrypt value.', 'enginemail-smtp' ) );
        }

        return $cipher;
    }

    /**
     * Decrypt data using the configured key material.
     *
     * @param string $data Cipher text.
     * @return string|WP_Error Plain text or WP_Error when key material is missing/invalid.
     */
    public static function decrypt( $data ) {
        if ( '' === $data ) {
            return '';
        }

        $creds = self::get_credentials();
        if ( is_wp_error( $creds ) ) {
            return $creds;
        }

        list( $key, $iv ) = $creds;

        $plain = openssl_decrypt( $data, self::ENCRYPTION_METHOD, $key, 0, $iv );
        if ( false === $plain ) {
            return new WP_Error( 'enginemail_decryption_failed', __( 'Unable to decrypt value.', 'enginemail-smtp' ) );
        }

        return $plain;
    }

    /**
     * Resolve the encryption key and IV from constants.
     *
     * @return array|WP_Error Array of key/iv binary strings or WP_Error when missing.
     */
    private static function get_credentials() {
        if ( ! defined( 'ENGINEMAIL_SMTP_KEY' ) || ! defined( 'ENGINEMAIL_SMTP_IV' ) ) {
            return new WP_Error( 'enginemail_missing_secret', __( 'Encryption constants are not defined in wp-config.php.', 'enginemail-smtp' ) );
        }

        $key_source = constant( 'ENGINEMAIL_SMTP_KEY' );
        $iv_source  = constant( 'ENGINEMAIL_SMTP_IV' );

        if ( '' === $key_source || '' === $iv_source ) {
            return new WP_Error( 'enginemail_invalid_secret', __( 'Encryption constants cannot be empty.', 'enginemail-smtp' ) );
        }

        $key = substr( hash( 'sha256', (string) $key_source, true ), 0, 32 );
        $iv  = substr( hash( 'sha256', (string) $iv_source, true ), 0, 16 );

        if ( 32 !== strlen( $key ) || 16 !== strlen( $iv ) ) {
            return new WP_Error( 'enginemail_secret_length', __( 'Encryption key or IV length is invalid.', 'enginemail-smtp' ) );
        }

        return array( $key, $iv );
    }
}
