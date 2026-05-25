<?php
/**
 * API client unit tests for the EnjinMel SMTP plugin.
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
 * Tests API client integration points with WordPress mail.
 */
class API_Client_Test extends WP_UnitTestCase {

	/**
	 * Prepare encrypted settings and filter state before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$encrypted_key = EnjinMel_SMTP_Encryption::encrypt( 'test-api-key' );
		if ( is_wp_error( $encrypted_key ) ) {
			$this->fail( 'Encryption setup failed in test bootstrap: ' . $encrypted_key->get_error_message() );
		}

		update_option(
			'enjinmel_smtp_settings',
			array(
				'api_key'        => $encrypted_key,
				'from_email'     => 'no-reply@example.com',
				'from_name'      => 'EnjinMel QA',
				'force_from'     => 1,
				'enable_logging' => 0,
			)
		);
	}

	/**
	 * Reset plugin settings and hooks after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		delete_option( 'enjinmel_smtp_settings' );
		remove_all_filters( 'pre_http_request' );
		if ( function_exists( 'remove_all_actions' ) ) {
			remove_all_actions( 'enjinmel_smtp_before_send' );
			remove_all_actions( 'enjinmel_smtp_after_send' );
		} else {
			remove_all_filters( 'enjinmel_smtp_before_send' );
			remove_all_filters( 'enjinmel_smtp_after_send' );
		}
		parent::tearDown();
	}

	/**
	 * Ensure wp_mail routes through REST and fires plugin hooks on success.
	 *
	 * @return void
	 */
	public function test_wp_mail_success_via_rest() {
		$requests    = array();
		$before_hook = array();
		$after_hook  = array();

		add_filter(
			'pre_http_request',
			function ( $preempt, $r, $url ) use ( &$requests ) {
				$requests[] = array(
					'args' => $r,
					'url'  => $url,
				);
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'Result' => array( 'StatusCode' => '200' ) ) ),
				);
			},
			10,
			3
		);

		add_action(
			'enjinmel_smtp_before_send',
			function ( $normalized, $payload ) use ( &$before_hook ) {
				$before_hook = array(
					'normalized' => $normalized,
					'payload'    => $payload,
				);
			},
			10,
			2
		);

		add_action(
			'enjinmel_smtp_after_send',
			function ( $normalized, $payload, $response ) use ( &$after_hook ) {
				$after_hook = array(
					'normalized' => $normalized,
					'payload'    => $payload,
					'response'   => $response,
				);
			},
			10,
			3
		);

		$result = wp_mail( 'recipient@example.com', 'REST Subject', 'Hello from REST transport.' );

		$this->assertTrue( $result, 'wp_mail() should return true on success.' );
		$this->assertNotEmpty( $requests, 'HTTP request should have been dispatched.' );

		$payload = json_decode( $requests[0]['args']['body'], true );
		$this->assertSame( 'recipient@example.com', $payload['ToEmail'] );
		$this->assertSame( 'REST Subject', $payload['Subject'] );
		$this->assertSame( 'no-reply@example.com', $payload['SenderEmail'] );
		$this->assertSame( 'EnjinMel QA', $payload['SenderName'] );
		$this->assertSame( 'Hello from REST transport.', $payload['SubmittedContent'] );
		$this->assertArrayNotHasKey( 'SubmittedContentType', $payload );
		$this->assertArrayNotHasKey( 'IsHtmlContent', $payload );

		$this->assertNotEmpty( $before_hook, 'enjinmel_smtp_before_send should receive data.' );
		$this->assertNotEmpty( $after_hook, 'enjinmel_smtp_after_send should receive data.' );
		$this->assertSame( $payload['Subject'], $before_hook['payload']['Subject'], 'Before hook payload should match submission payload.' );
		$this->assertSame( $payload['Subject'], $after_hook['payload']['Subject'], 'After hook payload should match submission payload.' );
		$this->assertSame( 'recipient@example.com', $before_hook['normalized']['to'][0], 'Normalized recipient should be passed to before hook.' );
		$this->assertSame( 'recipient@example.com', $after_hook['normalized']['to'][0], 'Normalized recipient should be passed to after hook.' );
		$this->assertIsArray( $after_hook['response'], 'After hook should receive the final API response array.' );
	}

	/**
	 * Ensure missing API configuration causes wp_mail to return WP_Error.
	 *
	 * @return void
	 */
	public function test_wp_mail_errors_when_api_key_missing() {
		delete_option( 'enjinmel_smtp_settings' );

		$result = wp_mail( 'recipient@example.com', 'Subject', 'Body' );

		$this->assertInstanceOf( WP_Error::class, $result, 'wp_mail should return WP_Error when API key is missing.' );
		$error_codes = $result->get_error_codes();
		$this->assertContains( 'enjinmel_rest_failure', $error_codes, 'Expected EnjinMel error code to be present.' );
		$this->assertContains( 'enginemail_rest_failure', $error_codes, 'Expected legacy EngineMail error code to remain for compatibility.' );
	}

	/**
	 * Ensure prior mail blockers are preserved.
	 *
	 * @return void
	 */
	public function test_pre_wp_mail_preserves_prior_wp_error() {
		$http_called = false;
		$prior_error = new WP_Error( 'blocked_by_policy', 'Security policy blocked this message.' );

		add_filter(
			'pre_http_request',
			function () use ( &$http_called ) {
				$http_called = true;
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'Result' => array( 'StatusCode' => '200' ) ) ),
				);
			}
		);

		$result = enjinmel_smtp_pre_wp_mail(
			$prior_error,
			array(
				'to'          => 'recipient@example.com',
				'subject'     => 'Blocked Subject',
				'message'     => 'Blocked message body.',
				'headers'     => array(),
				'attachments' => array(),
			)
		);

		$this->assertSame( $prior_error, $result, 'Existing WP_Error blockers should be preserved.' );
		$this->assertFalse( $http_called, 'No HTTP request should be dispatched after a prior WP_Error.' );
	}
}
