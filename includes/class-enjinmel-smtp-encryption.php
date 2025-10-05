<?php
/**
 * File-level hooks for EnjinMel SMTP encryption helpers.
 *
 * @package EnjinMel_SMTP
 */

/**
 * Lightweight wrapper around OpenSSL for option encryption.
 */
class EnjinMel_SMTP_Encryption
{

    private const ENCRYPTION_METHOD = 'AES-256-CBC';

    /**
     * Encrypt data using the configured key material.
     *
     * @param  string $data Plain text to encrypt.
     * @return string|WP_Error Base64 encoded cipher text or WP_Error on failure.
     */
    public static function encrypt( $data )
    {
        if ('' === $data ) {
            return '';
        }

        $creds = self::get_credentials();
        if (is_wp_error($creds) ) {
            return $creds;
        }

        list( $key, $iv ) = $creds;

        $cipher = openssl_encrypt($data, self::ENCRYPTION_METHOD, $key, 0, $iv);
        if (false === $cipher ) {
            return enjinmel_smtp_wp_error('enjinmel_encryption_failed', __('Unable to encrypt value.', 'enjinmel-smtp'), null, 'enginemail_encryption_failed');
        }

        return $cipher;
    }

    /**
     * Decrypt data using the configured key material.
     *
     * @param  string $data Cipher text.
     * @return string|WP_Error Plain text or WP_Error when key material is missing/invalid.
     */
    public static function decrypt( $data )
    {
        if ('' === $data ) {
            return '';
        }

        $creds = self::get_credentials();
        if (is_wp_error($creds) ) {
            return $creds;
        }

        list( $key, $iv ) = $creds;

        $plain = openssl_decrypt($data, self::ENCRYPTION_METHOD, $key, 0, $iv);
        if (false === $plain ) {
            return enjinmel_smtp_wp_error('enjinmel_decryption_failed', __('Unable to decrypt value.', 'enjinmel-smtp'), null, 'enginemail_decryption_failed');
        }

        return $plain;
    }

    /**
     * Resolve the encryption key and IV from constants or auto-generated stored keys.
     *
     * @return array|WP_Error Array of key/iv binary strings or WP_Error when missing.
     */
    private static function get_credentials()
    {
        $key_source = enjinmel_smtp_get_secret_constant('ENJINMEL_SMTP_KEY', 'ENGINEMAIL_SMTP_KEY');
        $iv_source  = enjinmel_smtp_get_secret_constant('ENJINMEL_SMTP_IV', 'ENGINEMAIL_SMTP_IV');

        if (null === $key_source || null === $iv_source ) {
            $stored = self::get_or_create_stored_keys();
            if (is_wp_error($stored) ) {
                return $stored;
            }
            list( $key_source, $iv_source ) = $stored;
        }

        if ('' === $key_source || '' === $iv_source ) {
            return enjinmel_smtp_wp_error('enjinmel_invalid_secret', __('Encryption constants cannot be empty.', 'enjinmel-smtp'), null, 'enginemail_invalid_secret');
        }

        $key = substr(hash('sha256', (string) $key_source, true), 0, 32);
        $iv  = substr(hash('sha256', (string) $iv_source, true), 0, 16);

        if (32 !== strlen($key) || 16 !== strlen($iv) ) {
            return enjinmel_smtp_wp_error('enjinmel_secret_length', __('Encryption key or IV length is invalid.', 'enjinmel-smtp'), null, 'enginemail_secret_length');
        }

        return array( $key, $iv );
    }

    /**
     * Get or create auto-generated encryption keys stored in the database.
     *
     * @return array|WP_Error Array of key/iv source strings or WP_Error on failure.
     */
    private static function get_or_create_stored_keys()
    {
        $key = get_option('enjinmel_smtp_encryption_key', null);
        $iv  = get_option('enjinmel_smtp_encryption_iv', null);

        if (null === $key || null === $iv ) {
            $key = self::generate_random_key();
            $iv  = self::generate_random_key();

            if (false === update_option('enjinmel_smtp_encryption_key', $key) || false === update_option('enjinmel_smtp_encryption_iv', $iv) ) {
                return enjinmel_smtp_wp_error('enjinmel_key_generation_failed', __('Failed to generate and store encryption keys.', 'enjinmel-smtp'), null, 'enginemail_key_generation_failed');
            }
        }

        return array( $key, $iv );
    }

    /**
     * Generate a cryptographically secure random key.
     *
     * @return string Random key string.
     */
    private static function generate_random_key()
    {
        return bin2hex(random_bytes(32));
    }
}
