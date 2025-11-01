<?php
/**
 * File-level hooks for EnjinMel SMTP encryption helpers.
 *
 * @package EnjinMel_SMTP
 */

/**
 * Lightweight wrapper around OpenSSL for option encryption.
 */
class EnjinMel_SMTP_Encryption {


	private const ENCRYPTION_METHOD = 'AES-256-CBC';

	/**
	 * Encrypt data using the configured key material.
	 *
	 * Uses a random IV per encryption (v2 format) for enhanced security.
	 * Format: v2:base64(IV || ciphertext)
	 *
	 * @param  string $data Plain text to encrypt.
	 * @return string|WP_Error Base64 encoded cipher text with IV or WP_Error on failure.
	 */
	public static function encrypt( $data ) {
		if ( '' === $data ) {
			return '';
		}

		$creds = self::get_credentials();
		if ( is_wp_error( $creds ) ) {
			return $creds;
		}

		list( $key ) = $creds; // Only need key for v2 encryption.

		// Generate random IV for this encryption.
		$iv_len = openssl_cipher_iv_length( self::ENCRYPTION_METHOD );
		$iv     = random_bytes( $iv_len );

		$raw = openssl_encrypt( $data, self::ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv );
		if ( false === $raw ) {
			return new WP_Error( 'enjinmel_encryption_failed', __( 'Unable to encrypt value.', 'enjinmel-smtp' ) );
		}

		// Version 2: Store IV with ciphertext for per-message IV.
		return 'v2:' . base64_encode( $iv . $raw );
	}

	/**
	 * Decrypt data using the configured key material.
	 *
	 * Supports both v2 format (random IV per message) and legacy format (static IV).
	 * v2 format: v2:base64(IV || ciphertext)
	 * Legacy format: base64(ciphertext)
	 *
	 * @param  string $data Cipher text.
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

		list( $key, $legacy_iv ) = $creds;

		// Version 2: Random IV embedded in ciphertext.
		if ( strncmp( $data, 'v2:', 3 ) === 0 ) {
			$blob = base64_decode( substr( $data, 3 ), true );
			if ( false === $blob ) {
				return new WP_Error( 'enjinmel_decryption_failed', __( 'Unable to decrypt value.', 'enjinmel-smtp' ) );
			}

			$iv_len = openssl_cipher_iv_length( self::ENCRYPTION_METHOD );
			$iv     = substr( $blob, 0, $iv_len );
			$raw    = substr( $blob, $iv_len );

			$plain = openssl_decrypt( $raw, self::ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv );
			if ( false === $plain ) {
				return new WP_Error( 'enjinmel_decryption_failed', __( 'Unable to decrypt value.', 'enjinmel-smtp' ) );
			}

			return $plain;
		}

		// Legacy format: Static IV from credentials.
		$plain = openssl_decrypt( $data, self::ENCRYPTION_METHOD, $key, 0, $legacy_iv );
		if ( false === $plain ) {
			return new WP_Error( 'enjinmel_decryption_failed', __( 'Unable to decrypt value.', 'enjinmel-smtp' ) );
		}

		return $plain;
	}

	/**
	 * Resolve the encryption key and IV from constants or auto-generated stored keys.
	 *
	 * @return array|WP_Error Array of key/iv binary strings or WP_Error when missing.
	 */
	private static function get_credentials() {
		$key_source = defined( 'ENJINMEL_SMTP_KEY' ) && '' !== constant( 'ENJINMEL_SMTP_KEY' ) ? constant( 'ENJINMEL_SMTP_KEY' ) : null;
		$iv_source  = defined( 'ENJINMEL_SMTP_IV' ) && '' !== constant( 'ENJINMEL_SMTP_IV' ) ? constant( 'ENJINMEL_SMTP_IV' ) : null;

		if ( null === $key_source || null === $iv_source ) {
			$stored = self::get_or_create_stored_keys();
			if ( is_wp_error( $stored ) ) {
				return $stored;
			}
			list( $key_source, $iv_source ) = $stored;
		}

		if ( '' === $key_source || '' === $iv_source ) {
			return new WP_Error( 'enjinmel_invalid_secret', __( 'Encryption constants cannot be empty.', 'enjinmel-smtp' ) );
		}

		$key = substr( hash( 'sha256', (string) $key_source, true ), 0, 32 );
		$iv  = substr( hash( 'sha256', (string) $iv_source, true ), 0, 16 );

		if ( 32 !== strlen( $key ) || 16 !== strlen( $iv ) ) {
			return new WP_Error( 'enjinmel_secret_length', __( 'Encryption key or IV length is invalid.', 'enjinmel-smtp' ) );
		}

		return array( $key, $iv );
	}

	/**
	 * Get or create auto-generated encryption keys stored in the database.
	 *
	 * @return array|WP_Error Array of key/iv source strings or WP_Error on failure.
	 */
	private static function get_or_create_stored_keys() {
		$key = get_option( 'enjinmel_smtp_encryption_key', null );
		$iv  = get_option( 'enjinmel_smtp_encryption_iv', null );

		if ( null === $key || null === $iv ) {
			$key = self::generate_random_key();
			$iv  = self::generate_random_key();

			// Use add_option with autoload=no to prevent loading secrets on every request.
			$key_added = add_option( 'enjinmel_smtp_encryption_key', $key, '', 'no' );
			$iv_added  = add_option( 'enjinmel_smtp_encryption_iv', $iv, '', 'no' );

			if ( ! $key_added || ! $iv_added ) {
				return new WP_Error( 'enjinmel_key_generation_failed', __( 'Failed to generate and store encryption keys.', 'enjinmel-smtp' ) );
			}
		} else {
			// Migration: Update existing options to autoload=no if they're currently autoloaded.
			global $wpdb;
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->options} SET autoload = 'no' WHERE option_name IN (%s, %s) AND autoload = 'yes'",
					'enjinmel_smtp_encryption_key',
					'enjinmel_smtp_encryption_iv'
				)
			);
		}

		return array( $key, $iv );
	}

	/**
	 * Generate a cryptographically secure random key.
	 *
	 * @return string Random key string.
	 */
	private static function generate_random_key() {
		return bin2hex( random_bytes( 32 ) );
	}
}
