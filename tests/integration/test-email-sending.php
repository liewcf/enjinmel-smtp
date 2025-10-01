<?php

if ( ! defined( 'ENJINMEL_SMTP_KEY' ) ) {
    define( 'ENJINMEL_SMTP_KEY', 'integration-test-key' );
}

if ( ! defined( 'ENJINMEL_SMTP_IV' ) ) {
    define( 'ENJINMEL_SMTP_IV', 'integration-test-iv' );
}

if ( ! function_exists( 'enjinmel_smtp_pre_wp_mail' ) ) {
    require_once dirname( __FILE__ ) . '/../../enjinmel-smtp.php';
}

class Test_Email_Sending extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();

        $encrypted_key = EnjinMel_SMTP_Encryption::encrypt( 'integration-api-key' );
        if ( is_wp_error( $encrypted_key ) ) {
            $this->fail( 'Failed to encrypt API key for integration test: ' . $encrypted_key->get_error_message() );
        }

        update_option( 'enjinmel_smtp_settings', array(
            'api_key'        => $encrypted_key,
            'from_email'     => 'no-reply@example.com',
            'from_name'      => 'EnjinMel Integration',
            'campaign_name'  => 'WordPress Integration',
            'force_from'     => 0,
            'enable_logging' => 1,
        ) );
    }

    public function tearDown(): void {
        delete_option( 'enjinmel_smtp_settings' );
        remove_all_filters( 'pre_http_request' );
        remove_all_actions( 'wp_mail_succeeded' );
        parent::tearDown();
    }

    public function test_rest_payload_includes_headers_and_triggers_success_action() {
        $requests = array();
        add_filter( 'pre_http_request', function( $pre, $args, $url ) use ( &$requests ) {
            $requests[] = array( 'args' => $args, 'url' => $url );
            return array(
                'response' => array( 'code' => 200 ),
                'body'     => wp_json_encode( array( 'Result' => array( 'StatusCode' => '200' ) ) ),
            );
        }, 10, 3 );

        $action_payload = null;
        add_action( 'wp_mail_succeeded', function( $data ) use ( &$action_payload ) {
            $action_payload = $data;
        }, 10, 1 );

        $headers = array(
            'From: Custom Sender <sender@example.com>',
            'Cc: copy@example.com',
            'Bcc: hidden@example.com',
            'Reply-To: helpdesk@example.com',
            'Content-Type: text/html; charset=UTF-8',
        );

        $result = wp_mail(
            array( 'primary@example.com', 'secondary@example.com' ),
            'Integration Subject',
            '<p>HTML body</p>',
            $headers
        );

        $this->assertTrue( $result );
        $this->assertNotNull( $action_payload );
        $this->assertSame( 'primary@example.com', is_array( $action_payload['to'] ) ? $action_payload['to'][0] : $action_payload['to'] );
        $this->assertSame( 'enjinmel_rest', $action_payload['transport'] );
        $this->assertSame( 'enginemail_rest', $action_payload['legacy_transport'] );

        $this->assertCount( 1, $requests );
        $payload = json_decode( $requests[0]['args']['body'], true );

        $this->assertSame( 'primary@example.com,secondary@example.com', $payload['ToEmail'] );
        $this->assertSame( 'Integration Subject', $payload['Subject'] );
        $this->assertSame( 'sender@example.com', $payload['SenderEmail'] );
        $this->assertSame( 'Custom Sender', $payload['SenderName'] );
        $this->assertSame( 'text/html; charset=utf-8', $payload['SubmittedContentType'] );
        $this->assertTrue( $payload['IsHtmlContent'] );
        $this->assertSame( array( 'copy@example.com' ), $payload['CCEmails'] );
        $this->assertSame( array( 'hidden@example.com' ), $payload['BCCEmails'] );
        $this->assertSame( 'WordPress Integration', $payload['CampaignName'] );
        $this->assertSame( 'helpdesk@example.com', $payload['ReplyToEmail'] );
    }
}
