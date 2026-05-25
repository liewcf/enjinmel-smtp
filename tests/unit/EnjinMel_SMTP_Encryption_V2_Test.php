<?php
/**
 * Test encryption v2 implementation
 *
 * @package EnjinMel_SMTP
 */

/**
 * Test case for per-message IV encryption
 */
class EnjinMel_SMTP_Encryption_V2_Test extends WP_UnitTestCase {

	/**
	 * Test that encrypt() produces v2 format
	 */
	public function test_encrypt_produces_v2_format() {
		$test_data = 'test-api-key-secret-12345';
		$encrypted = EnjinMel_SMTP_Encryption::encrypt( $test_data );

		$this->assertNotWPError( $encrypted, 'Encryption should not produce WP_Error' );
		$this->assertStringStartsWith( 'v2:', $encrypted, 'Encrypted data should start with v2: prefix' );
	}

	/**
	 * Test that encrypt() produces different ciphertext each time (random IV)
	 */
	public function test_encrypt_uses_random_iv() {
		$test_data = 'same-plaintext';
		$encrypted1 = EnjinMel_SMTP_Encryption::encrypt( $test_data );
		$encrypted2 = EnjinMel_SMTP_Encryption::encrypt( $test_data );

		$this->assertNotWPError( $encrypted1 );
		$this->assertNotWPError( $encrypted2 );
		$this->assertNotEquals( $encrypted1, $encrypted2, 'Same plaintext should produce different ciphertext (random IV)' );
	}

	/**
	 * Test that decrypt() can decrypt v2 format
	 */
	public function test_decrypt_v2_format() {
		$test_data = 'test-api-key-secret-12345';
		$encrypted = EnjinMel_SMTP_Encryption::encrypt( $test_data );
		$decrypted = EnjinMel_SMTP_Encryption::decrypt( $encrypted );

		$this->assertNotWPError( $decrypted, 'Decryption should not produce WP_Error' );
		$this->assertEquals( $test_data, $decrypted, 'Decrypted data should match original' );
	}

	/**
	 * Test that decrypt() still works with legacy format (backward compatibility)
	 */
	public function test_decrypt_legacy_format_backward_compatibility() {
		// Create a legacy encrypted value (static IV)
		$test_data = 'legacy-api-key';
		
		// Get credentials
		$reflection = new ReflectionClass( 'EnjinMel_SMTP_Encryption' );
		$method = $reflection->getMethod( 'get_credentials' );
		$method->setAccessible( true );
		$creds = $method->invoke( null );

		$this->assertNotWPError( $creds );
		list( $key, $iv ) = $creds;

		// Create legacy format encryption manually
		$legacy_encrypted = openssl_encrypt( $test_data, 'AES-256-CBC', $key, 0, $iv );

		// Test that decrypt() can still handle legacy format
		$decrypted = EnjinMel_SMTP_Encryption::decrypt( $legacy_encrypted );

		$this->assertNotWPError( $decrypted, 'Legacy decryption should not produce WP_Error' );
		$this->assertEquals( $test_data, $decrypted, 'Legacy decrypted data should match original' );
	}

	/**
	 * Test empty string handling
	 */
	public function test_empty_string_handling() {
		$encrypted = EnjinMel_SMTP_Encryption::encrypt( '' );
		$this->assertEquals( '', $encrypted, 'Empty string should encrypt to empty string' );

		$decrypted = EnjinMel_SMTP_Encryption::decrypt( '' );
		$this->assertEquals( '', $decrypted, 'Empty string should decrypt to empty string' );
	}

	/**
	 * Test round-trip encryption/decryption with various data
	 */
	public function test_round_trip_encryption() {
		$test_cases = array(
			'simple-key',
			'complex-key-with-special-chars-!@#$%^&*()',
			'very-long-' . str_repeat( 'x', 100 ) . '-key',
			'unicode-测试-🔐-key',
		);

		foreach ( $test_cases as $test_data ) {
			$encrypted = EnjinMel_SMTP_Encryption::encrypt( $test_data );
			$this->assertNotWPError( $encrypted, "Encryption failed for: {$test_data}" );

			$decrypted = EnjinMel_SMTP_Encryption::decrypt( $encrypted );
			$this->assertNotWPError( $decrypted, "Decryption failed for: {$test_data}" );
			$this->assertEquals( $test_data, $decrypted, "Round-trip failed for: {$test_data}" );
		}
	}

	/**
	 * Test that v2 format contains IV
	 */
	public function test_v2_format_contains_iv() {
		$test_data = 'test-key';
		$encrypted = EnjinMel_SMTP_Encryption::encrypt( $test_data );

		// Remove v2: prefix and decode
		$blob = base64_decode( substr( $encrypted, 3 ), true );
		$this->assertNotFalse( $blob, 'v2 format should be valid base64' );

		// Check that blob contains IV (16 bytes) + ciphertext
		$this->assertGreaterThan( 16, strlen( $blob ), 'Encrypted blob should contain IV (16 bytes) + ciphertext' );
	}
}
