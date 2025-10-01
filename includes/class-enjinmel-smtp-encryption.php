<?php
/**
 * Lightweight wrapper around OpenSSL for option encryption.
 *
 * @package EnjinMel_SMTP
 */
class EnjinMel_SMTP_Encryption {

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
			return enjinmel_smtp_wp_error( 'enjinmel_encryption_failed', __( 'Unable to encrypt value.', 'enjinmel-smtp' ), null, 'enginemail_encryption_failed' );
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
			return enjinmel_smtp_wp_error( 'enjinmel_decryption_failed', __( 'Unable to decrypt value.', 'enjinmel-smtp' ), null, 'enginemail_decryption_failed' );
		}

		return $plain;
	}

	/**
	 * Resolve the encryption key and IV from constants.
	 *
	 * @return array|WP_Error Array of key/iv binary strings or WP_Error when missing.
	 */
	private static function get_credentials() {
	$key_source = enjinmel_smtp_get_secret_constant( 'ENJINMEL_SMTP_KEY', 'ENGINEMAIL_SMTP_KEY' );
	$iv_source  = enjinmel_smtp_get_secret_constant( 'ENJINMEL_SMTP_IV', 'ENGINEMAIL_SMTP_IV' );

	if ( null === $key_source || null === $iv_source ) {
		return enjinmel_smtp_wp_error( 'enjinmel_missing_secret', __( 'Encryption constants are not defined in wp-config.php.', 'enjinmel-smtp' ), null, 'enginemail_missing_secret' );
	}

	if ( '' === $key_source || '' === $iv_source ) {
		return enjinmel_smtp_wp_error( 'enjinmel_invalid_secret', __( 'Encryption constants cannot be empty.', 'enjinmel-smtp' ), null, 'enginemail_invalid_secret' );
	}

		$key = substr( hash( 'sha256', (string) $key_source, true ), 0, 32 );
		$iv  = substr( hash( 'sha256', (string) $iv_source, true ), 0, 16 );

	if ( 32 !== strlen( $key ) || 16 !== strlen( $iv ) ) {
		return enjinmel_smtp_wp_error( 'enjinmel_secret_length', __( 'Encryption key or IV length is invalid.', 'enjinmel-smtp' ), null, 'enginemail_secret_length' );
	}

		return array( $key, $iv );
	}
}
