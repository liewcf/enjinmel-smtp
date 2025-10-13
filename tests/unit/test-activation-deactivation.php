<?php
/**
 * Activation and Deactivation unit tests for the EnjinMel SMTP plugin.
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
 * Test plugin activation and deactivation routines.
 */
class Test_Activation_Deactivation extends WP_UnitTestCase {

	/**
	 * Test that activation creates the enjinmel_smtp_logs table.
	 *
	 * @return void
	 */
	public function test_activation_creates_table() {
		global $wpdb;

		$table_name = enjinmel_smtp_log_table_name();

		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

		$this->assertFalse( enjinmel_smtp_table_exists( $table_name ) );

		enjinmel_smtp_activate();

		$this->assertTrue( enjinmel_smtp_table_exists( $table_name ) );
	}

	/**
	 * Test that activation table has correct structure.
	 *
	 * @return void
	 */
	public function test_activation_table_structure() {
		global $wpdb;

		enjinmel_smtp_activate();

		$table_name = enjinmel_smtp_log_table_name();
		$columns = $wpdb->get_results( "DESCRIBE {$table_name}" );

		$column_names = wp_list_pluck( $columns, 'Field' );

		$this->assertContains( 'id', $column_names );
		$this->assertContains( 'timestamp', $column_names );
		$this->assertContains( 'to_email', $column_names );
		$this->assertContains( 'subject', $column_names );
		$this->assertContains( 'status', $column_names );
		$this->assertContains( 'error_message', $column_names );
	}

	/**
	 * Test that activation migrates data from legacy table.
	 *
	 * @return void
	 */
	public function test_activation_migrates_legacy_logs() {
		global $wpdb;

		$legacy_table = enjinmel_smtp_legacy_log_table_name();
		$new_table    = enjinmel_smtp_log_table_name();

		$wpdb->query( "DROP TABLE IF EXISTS {$new_table}" );
		$wpdb->query( "DROP TABLE IF EXISTS {$legacy_table}" );

		$charset_collate = $wpdb->get_charset_collate();
		$wpdb->query(
			"CREATE TABLE {$legacy_table} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                timestamp DATETIME NOT NULL,
                to_email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                status VARCHAR(20) NOT NULL,
                error_message TEXT,
                PRIMARY KEY  (id),
                KEY timestamp (timestamp)
            ) {$charset_collate};"
		);

		$wpdb->insert(
			$legacy_table,
			array(
				'timestamp'     => current_time( 'mysql' ),
				'to_email'      => 'test@example.com',
				'subject'       => 'Legacy Test Email',
				'status'        => 'sent',
				'error_message' => null,
			)
		);

		$legacy_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$legacy_table}" );
		$this->assertEquals( 1, $legacy_count );

		enjinmel_smtp_activate();

		$new_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$new_table}" );
		$this->assertEquals( 1, $new_count );

		$row = $wpdb->get_row( "SELECT * FROM {$new_table} LIMIT 1" );
		$this->assertEquals( 'test@example.com', $row->to_email );
		$this->assertEquals( 'Legacy Test Email', $row->subject );
		$this->assertEquals( 'sent', $row->status );
	}

	/**
	 * Test that activation migrates settings from legacy option.
	 *
	 * @return void
	 */
	public function test_activation_migrates_legacy_settings() {
		delete_option( 'enjinmel_smtp_settings' );

		$legacy_key = EnjinMel_SMTP_Encryption::encrypt( 'legacy-migration-key' );
		$legacy_settings = array(
			'api_key'         => $legacy_key,
			'campaign_name'   => 'Legacy Migration Campaign',
			'from_email'      => 'migration@example.com',
		);

		update_option( 'enginemail_smtp_settings', $legacy_settings );

		enjinmel_smtp_activate();

		$new_settings = get_option( 'enjinmel_smtp_settings' );
		$this->assertNotEmpty( $new_settings );
		$this->assertEquals( $legacy_key, $new_settings['api_key'] );
		$this->assertEquals( 'Legacy Migration Campaign', $new_settings['campaign_name'] );

		delete_option( 'enginemail_smtp_settings' );
		delete_option( 'enjinmel_smtp_settings' );
	}

	/**
	 * Test that activation schedules cron event.
	 *
	 * @return void
	 */
	public function test_activation_schedules_cron() {
		wp_clear_scheduled_hook( enjinmel_smtp_cron_hook() );

		$this->assertFalse( wp_next_scheduled( enjinmel_smtp_cron_hook() ) );

		enjinmel_smtp_activate();

		$next_run = wp_next_scheduled( enjinmel_smtp_cron_hook() );
		$this->assertNotFalse( $next_run );
		$this->assertIsInt( $next_run );
	}

	/**
	 * Test that deactivation unschedules new cron event.
	 *
	 * @return void
	 */
	public function test_deactivation_unschedules_new_cron() {
		wp_schedule_event( time(), 'daily', enjinmel_smtp_cron_hook() );

		$this->assertNotFalse( wp_next_scheduled( enjinmel_smtp_cron_hook() ) );

		enjinmel_smtp_deactivate();

		$this->assertFalse( wp_next_scheduled( enjinmel_smtp_cron_hook() ) );
	}

	/**
	 * Test that deactivation unschedules legacy cron event.
	 *
	 * @return void
	 */
	public function test_deactivation_unschedules_legacy_cron() {
		wp_schedule_event( time(), 'daily', enjinmel_smtp_legacy_cron_hook() );

		$this->assertNotFalse( wp_next_scheduled( enjinmel_smtp_legacy_cron_hook() ) );

		enjinmel_smtp_deactivate();

		$this->assertFalse( wp_next_scheduled( enjinmel_smtp_legacy_cron_hook() ) );
	}

	/**
	 * Test that reactivation reschedules cron events.
	 *
	 * @return void
	 */
	public function test_reactivation_reschedules_cron() {
		enjinmel_smtp_deactivate();

		$this->assertFalse( wp_next_scheduled( enjinmel_smtp_cron_hook() ) );

		enjinmel_smtp_activate();

		$next_run = wp_next_scheduled( enjinmel_smtp_cron_hook() );
		$this->assertNotFalse( $next_run );
		$this->assertIsInt( $next_run );
	}

	/**
	 * Test that activation doesn't duplicate existing logs.
	 *
	 * @return void
	 */
	public function test_activation_no_duplicate_logs() {
		global $wpdb;

		enjinmel_smtp_activate();

		$table = enjinmel_smtp_log_table_name();
		$wpdb->insert(
			$table,
			array(
				'timestamp'     => current_time( 'mysql' ),
				'to_email'      => 'unique@example.com',
				'subject'       => 'Unique Test',
				'status'        => 'sent',
				'error_message' => null,
			)
		);

		$count_before = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE subject = 'Unique Test'" );

		enjinmel_smtp_activate();

		$count_after = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE subject = 'Unique Test'" );

		$this->assertEquals( $count_before, $count_after );
	}
}
