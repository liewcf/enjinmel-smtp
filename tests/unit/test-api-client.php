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

class Test_API_Client extends WP_UnitTestCase {

    protected function setUp(): void {
        parent::setUp();

        $encrypted_key = EngineMail_SMTP_Encryption::encrypt( 'test-api-key' );
        if ( is_wp_error( $encrypted_key ) ) {
            $this->fail( 'Encryption setup failed in test bootstrap: ' . $encrypted_key->get_error_message() );
        }

        update_option( 'enginemail_smtp_settings', array(
            'api_key'        => $encrypted_key,
            'from_email'     => 'no-reply@example.com',
            'from_name'      => 'EngineMail QA',
            'force_from'     => 1,
            'enable_logging' => 0,
        ) );
    }

    protected function tearDown(): void {
        delete_option( 'enginemail_smtp_settings' );
        remove_all_filters( 'pre_http_request' );
        if ( function_exists( 'remove_all_actions' ) ) {
            remove_all_actions( 'enginemail_smtp_before_send' );
            remove_all_actions( 'enginemail_smtp_after_send' );
        } else {
            remove_all_filters( 'enginemail_smtp_before_send' );
            remove_all_filters( 'enginemail_smtp_after_send' );
        }
        parent::tearDown();
    }

    public function test_wp_mail_success_via_rest() {
        $requests = array();
        $before_hook = array();
        $after_hook  = array();

        add_filter( 'pre_http_request', function( $preempt, $r, $url ) use ( &$requests ) {
            $requests[] = array( 'args' => $r, 'url' => $url );
            return array(
                'response' => array( 'code' => 200 ),
                'body'     => wp_json_encode( array( 'Result' => array( 'StatusCode' => '200' ) ) ),
            );
        }, 10, 3 );

        add_action( 'enginemail_smtp_before_send', function( $normalized, $payload ) use ( &$before_hook ) {
            $before_hook = array(
                'normalized' => $normalized,
                'payload'    => $payload,
            );
        }, 10, 2 );

        add_action( 'enginemail_smtp_after_send', function( $normalized, $payload, $response ) use ( &$after_hook ) {
            $after_hook = array(
                'normalized' => $normalized,
                'payload'    => $payload,
                'response'   => $response,
            );
        }, 10, 3 );

        $result = wp_mail( 'recipient@example.com', 'REST Subject', 'Hello from REST transport.' );

        $this->assertTrue( $result, 'wp_mail() should return true on success.' );
        $this->assertNotEmpty( $requests, 'HTTP request should have been dispatched.' );

        $payload = json_decode( $requests[0]['args']['body'], true );
        $this->assertSame( 'recipient@example.com', $payload['ToEmail'] );
        $this->assertSame( 'REST Subject', $payload['Subject'] );
        $this->assertSame( 'no-reply@example.com', $payload['SenderEmail'] );
        $this->assertSame( 'EngineMail QA', $payload['SenderName'] );
        $this->assertSame( 'text/plain', $payload['SubmittedContentType'] );
        $this->assertFalse( $payload['IsHtmlContent'] );

        $this->assertNotEmpty( $before_hook, 'enginemail_smtp_before_send should receive data.' );
        $this->assertNotEmpty( $after_hook, 'enginemail_smtp_after_send should receive data.' );
        $this->assertSame( $payload['Subject'], $before_hook['payload']['Subject'], 'Before hook payload should match submission payload.' );
        $this->assertSame( $payload['Subject'], $after_hook['payload']['Subject'], 'After hook payload should match submission payload.' );
        $this->assertSame( 'recipient@example.com', $before_hook['normalized']['to'][0], 'Normalized recipient should be passed to before hook.' );
        $this->assertSame( 'recipient@example.com', $after_hook['normalized']['to'][0], 'Normalized recipient should be passed to after hook.' );
        $this->assertIsArray( $after_hook['response'], 'After hook should receive the final API response array.' );
    }

    public function test_wp_mail_errors_when_api_key_missing() {
        delete_option( 'enginemail_smtp_settings' );

        $result = wp_mail( 'recipient@example.com', 'Subject', 'Body' );

        $this->assertInstanceOf( WP_Error::class, $result, 'wp_mail should return WP_Error when API key is missing.' );
        $this->assertContains( 'enginemail_rest_failure', $result->get_error_codes(), 'Expected EngineMail error code to be present.' );
    }
}
