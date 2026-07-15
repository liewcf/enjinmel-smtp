<?php
/**
 * Admin asset tests for the EnjinMel SMTP plugin.
 *
 * @package EnjinMel_SMTP_Tests
 */

/**
 * Test settings-page asset loading and rendered output.
 */
class Admin_Assets_Test extends WP_UnitTestCase {

	/**
	 * Reset the settings script before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_dequeue_script( 'enjinmel-smtp-settings' );
		wp_deregister_script( 'enjinmel-smtp-settings' );
	}

	/**
	 * Reset the settings script after each test.
	 */
	public function tearDown(): void {
		wp_dequeue_script( 'enjinmel-smtp-settings' );
		wp_deregister_script( 'enjinmel-smtp-settings' );
		parent::tearDown();
	}

	/**
	 * Settings assets load on the plugin settings screen with runtime data.
	 */
	public function test_settings_assets_load_on_settings_screen() {
		enjinmel_smtp_enqueue_settings_assets( 'toplevel_page_enjinmel_smtp' );

		$this->assertTrue( wp_script_is( 'enjinmel-smtp-settings', 'enqueued' ) );

		$localized_data = wp_scripts()->get_data( 'enjinmel-smtp-settings', 'data' );
		$this->assertIsString( $localized_data );
		$this->assertStringContainsString( 'enjinmelSmtpSettings', $localized_data );
		$this->assertStringContainsString( 'enjinmel_smtp_send_test_email', $localized_data );
	}

	/**
	 * Settings assets do not load on unrelated admin screens.
	 */
	public function test_settings_assets_do_not_load_on_other_admin_screens() {
		enjinmel_smtp_enqueue_settings_assets( 'dashboard_page_unrelated' );

		$this->assertFalse( wp_script_is( 'enjinmel-smtp-settings', 'enqueued' ) );
	}

	/**
	 * Settings controls do not render JavaScript tags directly.
	 */
	public function test_settings_controls_do_not_render_script_tags() {
		enjinmel_smtp_settings_init();
		EnjinMel_SMTP_Settings_Page::add_settings_fields();

		ob_start();
		enjinmel_smtp_options_page();
		$output = ob_get_clean();

		$this->assertStringNotContainsString( '<script', strtolower( $output ) );
	}
}
