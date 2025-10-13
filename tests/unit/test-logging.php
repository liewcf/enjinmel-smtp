<?php
/**
 * Logging unit tests for the EnjinMel SMTP plugin.
 *
 * @package EnjinMel_SMTP_Tests
 */

if ( ! defined( 'ENJINMEL_SMTP_KEY' ) ) {
	define( 'ENJINMEL_SMTP_KEY', 'unit-test-key' );
}

if ( ! defined( 'ENJINMEL_SMTP_IV' ) ) {
	define( 'ENJINMEL_SMTP_IV', 'unit-test-iv' );
}

if ( ! function_exists( 'enjinmel_smtp_pre_wp_mail' ) ) {
	require_once __DIR__ . '/../../enjinmel-smtp.php';
}

/**
 * Tests the logging helpers for the EnjinMel SMTP plugin.
 */
class Test_Logging extends WP_UnitTestCase {

	/**
	 * Holds the plugin log table name for assertions.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Create the logging table and truncate between tests.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		global $wpdb;
		$this->table = $wpdb->prefix . 'enjinmel_smtp_logs';

		enjinmel_smtp_activate();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name assembled from the core-maintained prefix.
		$wpdb->query( "TRUNCATE TABLE {$this->table}" );
	}

	/**
	 * Clean up database state and scheduled events.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name assembled from the core-maintained prefix.
		$wpdb->query( "TRUNCATE TABLE {$this->table}" );

		remove_all_filters( 'enjinmel_smtp_log_entry' );
		remove_all_filters( 'enjinmel_smtp_retention_days' );
		remove_all_filters( 'enjinmel_smtp_retention_max_rows' );
		remove_all_filters( 'enginemail_smtp_log_entry' );
		remove_all_filters( 'enginemail_smtp_retention_days' );
		remove_all_filters( 'enginemail_smtp_retention_max_rows' );

		EnjinMel_SMTP_Logging::unschedule_events();

		parent::tearDown();
	}

	/**
	 * Validate that the log entry filter can mutate the stored row.
	 *
	 * @return void
	 */
	public function test_log_entry_filter_can_mutate_data() {
		add_filter(
			'enjinmel_smtp_log_entry',
			function ( $entry ) {
				$entry['subject'] = 'Filtered Subject';
				$entry['status']  = 'failed';
				return $entry;
			}
		);

		EnjinMel_SMTP_Logging::on_mail_succeeded(
			array(
				'to'      => 'recipient@example.com',
				'subject' => 'Original Subject',
			)
		);

			global $wpdb;
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name assembled from the core-maintained prefix.
			$sql     = "SELECT subject, status FROM {$this->table} ORDER BY id DESC LIMIT %d";
				$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query executed against isolated test tables.
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL placeholder coverage validated in test scope.
					$wpdb->prepare( $sql, 1 ),
					ARRAY_A
				);

		$this->assertNotNull( $row, 'Log row should be inserted.' );
		$this->assertSame( 'Filtered Subject', $row['subject'] );
		$this->assertSame( 'failed', $row['status'] );
	}

	/**
	 * Ensure retention days filter controls which rows are purged.
	 *
	 * @return void
	 */
	public function test_purge_logs_respects_retention_days() {
		$this->insert_log_row( $this->offset_time( '-3 days' ), 'Old Log' );
		$this->insert_log_row( $this->offset_time( '-6 hours' ), 'Fresh Log' );

		add_filter(
			'enjinmel_smtp_retention_days',
			function () {
				return 1;
			}
		);

		EnjinMel_SMTP_Logging::purge_logs();

			global $wpdb;
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name assembled from the core-maintained prefix.
			$sql      = "SELECT subject FROM {$this->table} ORDER BY id ASC";
			$subjects = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name assembled from the core-maintained prefix.

		$this->assertSame( array( 'Fresh Log' ), $subjects );
	}

	/**
	 * Ensure maximum row retention keeps the most recent entries.
	 *
	 * @return void
	 */
	public function test_purge_logs_respects_max_rows() {
		add_filter(
			'enjinmel_smtp_retention_days',
			function () {
				return 0;
			}
		);

		$this->insert_log_row( $this->offset_time( '-4 hours' ), 'Log 1' );
		$this->insert_log_row( $this->offset_time( '-3 hours' ), 'Log 2' );
		$this->insert_log_row( $this->offset_time( '-2 hours' ), 'Log 3' );
		$this->insert_log_row( $this->offset_time( '-1 hour' ), 'Log 4' );

		add_filter(
			'enjinmel_smtp_retention_max_rows',
			function () {
				return 2;
			}
		);

		EnjinMel_SMTP_Logging::purge_logs();

			global $wpdb;
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name assembled from the core-maintained prefix.
			$sql      = "SELECT subject FROM {$this->table} ORDER BY id ASC";
			$subjects = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name assembled from the core-maintained prefix.

		$this->assertSame( array( 'Log 3', 'Log 4' ), $subjects );
	}

	/**
	 * Insert a row into the plugin log table for test setup.
	 *
	 * @param string $timestamp Datetime string representing when the email was logged.
	 * @param string $subject   Email subject line.
	 * @param string $status    Final status of the log entry.
	 * @return void
	 */
	private function insert_log_row( $timestamp, $subject, $status = 'sent' ) {
		global $wpdb;

			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct insert scoped to the dedicated test table.
				$this->table,
				array(
					'timestamp'     => $timestamp,
					'to_email'      => 'recipient@example.com',
					'subject'       => $subject,
					'status'        => $status,
					'error_message' => '',
				),
				array( '%s', '%s', '%s', '%s', '%s' )
			);
	}

	/**
	 * Produce a WordPress-local datetime string offset from now.
	 *
	 * @param string $modifier Relative strtotime modifier (for example, '-1 hour').
	 * @return string MySQL formatted datetime.
	 */
	private function offset_time( $modifier ) {
		$base     = time();
		$adjusted = strtotime( $modifier, $base );
		if ( false === $adjusted ) {
			return gmdate( 'Y-m-d H:i:s', $base );
		}

		if ( function_exists( 'wp_date' ) ) {
			return wp_date( 'Y-m-d H:i:s', $adjusted, new \DateTimeZone( 'UTC' ) );
		}

		return gmdate( 'Y-m-d H:i:s', $adjusted );
	}
}
