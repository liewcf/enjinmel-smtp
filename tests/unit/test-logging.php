<?php

if ( ! defined( 'ENGINEMAIL_SMTP_KEY' ) ) {
    define( 'ENGINEMAIL_SMTP_KEY', 'unit-test-key' );
}

if ( ! defined( 'ENGINEMAIL_SMTP_IV' ) ) {
    define( 'ENGINEMAIL_SMTP_IV', 'unit-test-iv' );
}

if ( ! function_exists( 'enginemail_smtp_pre_wp_mail' ) ) {
    require_once dirname( __FILE__ ) . '/../../enginemail-smtp.php';
}

class Test_Logging extends WP_UnitTestCase {

    /**
     * @var string
     */
    private $table;

    protected function setUp(): void {
        parent::setUp();

        global $wpdb;
        $this->table = $wpdb->prefix . 'enginemail_smtp_logs';

        enginemail_smtp_activate();
        $wpdb->query( "TRUNCATE TABLE {$this->table}" );
    }

    protected function tearDown(): void {
        global $wpdb;
        $wpdb->query( "TRUNCATE TABLE {$this->table}" );

        remove_all_filters( 'enginemail_smtp_log_entry' );
        remove_all_filters( 'enginemail_smtp_retention_days' );
        remove_all_filters( 'enginemail_smtp_retention_max_rows' );

        EngineMail_SMTP_Logging::unschedule_events();

        parent::tearDown();
    }

    public function test_log_entry_filter_can_mutate_data() {
        add_filter( 'enginemail_smtp_log_entry', function( $entry ) {
            $entry['subject'] = 'Filtered Subject';
            $entry['status']  = 'failed';
            return $entry;
        } );

        EngineMail_SMTP_Logging::on_mail_succeeded( array(
            'to'      => 'recipient@example.com',
            'subject' => 'Original Subject',
        ) );

        global $wpdb;
        $row = $wpdb->get_row( "SELECT subject, status FROM {$this->table} ORDER BY id DESC LIMIT 1", ARRAY_A );

        $this->assertNotNull( $row, 'Log row should be inserted.' );
        $this->assertSame( 'Filtered Subject', $row['subject'] );
        $this->assertSame( 'failed', $row['status'] );
    }

    public function test_purge_logs_respects_retention_days() {
        $this->insert_log_row( $this->offset_time( '-3 days' ), 'Old Log' );
        $this->insert_log_row( $this->offset_time( '-6 hours' ), 'Fresh Log' );

        add_filter( 'enginemail_smtp_retention_days', function() {
            return 1;
        } );

        EngineMail_SMTP_Logging::purge_logs();

        global $wpdb;
        $subjects = $wpdb->get_col( "SELECT subject FROM {$this->table} ORDER BY id ASC" );

        $this->assertSame( array( 'Fresh Log' ), $subjects );
    }

    public function test_purge_logs_respects_max_rows() {
        add_filter( 'enginemail_smtp_retention_days', function() {
            return 0;
        } );

        $this->insert_log_row( $this->offset_time( '-4 hours' ), 'Log 1' );
        $this->insert_log_row( $this->offset_time( '-3 hours' ), 'Log 2' );
        $this->insert_log_row( $this->offset_time( '-2 hours' ), 'Log 3' );
        $this->insert_log_row( $this->offset_time( '-1 hour' ), 'Log 4' );

        add_filter( 'enginemail_smtp_retention_max_rows', function() {
            return 2;
        } );

        EngineMail_SMTP_Logging::purge_logs();

        global $wpdb;
        $subjects = $wpdb->get_col( "SELECT subject FROM {$this->table} ORDER BY id ASC" );

        $this->assertSame( array( 'Log 3', 'Log 4' ), $subjects );
    }

    private function insert_log_row( $timestamp, $subject, $status = 'sent' ) {
        global $wpdb;

        $wpdb->insert(
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

    private function offset_time( $modifier ) {
        $base      = current_time( 'timestamp' );
        $adjusted  = strtotime( $modifier, $base );
        if ( false === $adjusted ) {
            return current_time( 'mysql' );
        }

        if ( function_exists( 'wp_date' ) ) {
            return wp_date( 'Y-m-d H:i:s', $adjusted );
        }

        $offset = get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS;
        return gmdate( 'Y-m-d H:i:s', $adjusted + $offset );
    }
}
