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
        parent::tearDown();
    }

    public function test_wp_mail_success_via_rest() {
        $requests = array();

        add_filter( 'pre_http_request', function( $preempt, $r, $url ) use ( &$requests ) {
            $requests[] = array( 'args' => $r, 'url' => $url );
            return array(
                'response' => array( 'code' => 200 ),
                'body'     => wp_json_encode( array( 'Result' => array( 'StatusCode' => '200' ) ) ),
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
    }

    public function test_wp_mail_errors_when_api_key_missing() {
        delete_option( 'enginemail_smtp_settings' );

        $result = wp_mail( 'recipient@example.com', 'Subject', 'Body' );

        $this->assertInstanceOf( WP_Error::class, $result, 'wp_mail should return WP_Error when API key is missing.' );
        $this->assertContains( 'enginemail_rest_failure', $result->get_error_codes(), 'Expected EngineMail error code to be present.' );
    }
}
