<?php
/**
 * Settings Page unit tests for the EnjinMel SMTP plugin.
 *
 * @package EnjinMel_SMTP_Tests
 */

if ( ! defined( 'ENJINMEL_SMTP_KEY' ) ) {
	define( 'ENJINMEL_SMTP_KEY', 'unit-test-key' );
}

if ( ! defined( 'ENJINMEL_SMTP_IV' ) ) {
	define( 'ENJINMEL_SMTP_IV', 'unit-test-iv' );
}

/**
 * Test settings sanitization and encryption functionality.
 */
class Test_Settings_Page extends WP_UnitTestCase {

	/**
	 * Test that enjinmel_smtp_settings_sanitize properly sanitizes all fields.
	 *
	 * @return void
	 */
	public function test_settings_sanitize_basic_fields() {
		$input = array(
			'campaign_name'   => '  Test Campaign  ',
			'template_id'     => '  12345  ',
			'from_name'       => '<script>alert("xss")</script>Test Sender',
			'from_email'      => 'test@example.com',
			'force_from'      => '1',
			'enable_logging'  => '1',
		);

		$output = enjinmel_smtp_settings_sanitize( $input );

		$this->assertEquals( 'Test Campaign', $output['campaign_name'] );
		$this->assertEquals( '12345', $output['template_id'] );
		$this->assertStringNotContainsString( '<script>', $output['from_name'] );
		$this->assertEquals( 'test@example.com', $output['from_email'] );
		$this->assertEquals( 1, $output['force_from'] );
		$this->assertEquals( 1, $output['enable_logging'] );
	}

	/**
	 * Test that API key gets encrypted during sanitization.
	 *
	 * @return void
	 */
	public function test_settings_sanitize_encrypts_api_key() {
		$input = array(
			'api_key'         => 'my-secret-api-key-123',
			'campaign_name'   => 'Test',
			'from_email'      => 'test@example.com',
		);

		$output = enjinmel_smtp_settings_sanitize( $input );

		$this->assertNotEquals( 'my-secret-api-key-123', $output['api_key'] );
		$this->assertNotEmpty( $output['api_key'] );

		$decrypted = EnjinMel_SMTP_Encryption::decrypt( $output['api_key'] );
		$this->assertEquals( 'my-secret-api-key-123', $decrypted );
	}

	/**
	 * Test that empty API key preserves existing encrypted key.
	 *
	 * @return void
	 */
	public function test_settings_sanitize_preserves_existing_api_key() {
		$existing_key = EnjinMel_SMTP_Encryption::encrypt( 'existing-api-key' );
		update_option( 'enjinmel_smtp_settings', array( 'api_key' => $existing_key ) );

		$input = array(
			'api_key'         => '',
			'campaign_name'   => 'Test',
			'from_email'      => 'test@example.com',
		);

		$output = enjinmel_smtp_settings_sanitize( $input );

		$this->assertEquals( $existing_key, $output['api_key'] );

		delete_option( 'enjinmel_smtp_settings' );
	}

	/**
	 * Test that invalid email addresses are sanitized.
	 *
	 * @return void
	 */
	public function test_settings_sanitize_invalid_email() {
		$input = array(
			'from_email'      => 'not-a-valid-email',
			'campaign_name'   => 'Test',
		);

		$output = enjinmel_smtp_settings_sanitize( $input );

		$this->assertEquals( '', $output['from_email'] );
	}

	/**
	 * Test that enable_logging defaults to enabled on first save.
	 *
	 * @return void
	 */
	public function test_settings_sanitize_enable_logging_default() {
		delete_option( 'enjinmel_smtp_settings' );

		$input = array(
			'campaign_name'   => 'Test',
		);

		$output = enjinmel_smtp_settings_sanitize( $input );

		$this->assertEquals( 1, $output['enable_logging'] );
	}

	/**
	 * Test that enable_logging can be explicitly disabled.
	 *
	 * @return void
	 */
	public function test_settings_sanitize_disable_logging() {
		$input = array(
			'campaign_name'   => 'Test',
			'enable_logging'  => '0',
		);

		$output = enjinmel_smtp_settings_sanitize( $input );

		$this->assertEquals( 0, $output['enable_logging'] );
	}

	/**
	 * Test settings migration from legacy option name.
	 *
	 * @return void
	 */
	public function test_settings_migration_from_legacy() {
		$legacy_key = EnjinMel_SMTP_Encryption::encrypt( 'legacy-api-key' );
		$legacy_settings = array(
			'api_key'         => $legacy_key,
			'campaign_name'   => 'Legacy Campaign',
			'from_email'      => 'legacy@example.com',
			'enable_logging'  => 1,
		);

		update_option( 'enginemail_smtp_settings', $legacy_settings );
		delete_option( 'enjinmel_smtp_settings' );

		$settings = enjinmel_smtp_get_settings();

		$this->assertNotEmpty( $settings );
		$this->assertEquals( $legacy_key, $settings['api_key'] );
		$this->assertEquals( 'Legacy Campaign', $settings['campaign_name'] );
		$this->assertEquals( 'legacy@example.com', $settings['from_email'] );

		delete_option( 'enginemail_smtp_settings' );
		delete_option( 'enjinmel_smtp_settings' );
	}

	/**
	 * Test that force_from checkbox defaults to 0 when not checked.
	 *
	 * @return void
	 */
	public function test_settings_sanitize_force_from_unchecked() {
		$input = array(
			'campaign_name'   => 'Test',
		);

		$output = enjinmel_smtp_settings_sanitize( $input );

		$this->assertEquals( 0, $output['force_from'] );
	}

	/**
	 * Test legacy settings sanitization function.
	 *
	 * @return void
	 */
	public function test_legacy_settings_sanitize() {
		delete_option( 'enjinmel_smtp_settings' );

		$input = array(
			'campaign_name'   => 'Legacy Test',
			'from_email'      => 'legacy@test.com',
			'api_key'         => 'legacy-key-456',
		);

		$output = enjinmel_smtp_settings_sanitize_legacy( $input );

		$this->assertEquals( 'Legacy Test', $output['campaign_name'] );

		$new_settings = enjinmel_smtp_get_settings();
		$this->assertEquals( 'Legacy Test', $new_settings['campaign_name'] );

		delete_option( 'enjinmel_smtp_settings' );
	}
}
