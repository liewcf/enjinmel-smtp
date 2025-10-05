<?php
/**
 * AJAX Handler unit tests for the EnjinMel SMTP plugin.
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
 * Test AJAX handler for sending test emails.
 */
class Test_AJAX_Handler extends WP_UnitTestCase {

	/**
	 * Set up test user with admin capabilities.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_user );
	}

	/**
	 * Test that AJAX handler requires valid nonce.
	 *
	 * @return void
	 */
	public function test_send_test_email_requires_nonce() {
		$_POST['to'] = 'test@example.com';

		$this->expectException( 'WPAjaxDieContinueException' );

		try {
			$this->_handleAjax( 'enjinmel_smtp_send_test_email' );
		} catch ( WPAjaxDieContinueException $e ) {
			$response = json_decode( $this->_last_response, true );
			$this->assertEquals( -1, $response );
			throw $e;
		}
	}

	/**
	 * Test that AJAX handler requires admin capability.
	 *
	 * @return void
	 */
	public function test_send_test_email_requires_admin() {
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$_POST['nonce'] = wp_create_nonce( 'enjinmel_smtp_send_test_email' );
		$_POST['to']    = 'test@example.com';

		$this->expectException( 'WPAjaxDieContinueException' );

		try {
			$this->_handleAjax( 'enjinmel_smtp_send_test_email' );
		} catch ( WPAjaxDieContinueException $e ) {
			$response = json_decode( $this->_last_response, true );
			$this->assertArrayHasKey( 'data', $response );
			$this->assertArrayHasKey( 'message', $response['data'] );
			$this->assertStringContainsString( 'Unauthorized', $response['data']['message'] );
			throw $e;
		}
	}

	/**
	 * Test that AJAX handler validates email address.
	 *
	 * @return void
	 */
	public function test_send_test_email_validates_email() {
		$_POST['nonce'] = wp_create_nonce( 'enjinmel_smtp_send_test_email' );
		$_POST['to']    = 'not-a-valid-email';

		$this->expectException( 'WPAjaxDieContinueException' );

		try {
			$this->_handleAjax( 'enjinmel_smtp_send_test_email' );
		} catch ( WPAjaxDieContinueException $e ) {
			$response = json_decode( $this->_last_response, true );
			$this->assertFalse( $response['success'] );
			$this->assertArrayHasKey( 'data', $response );
			$this->assertArrayHasKey( 'message', $response['data'] );
			$this->assertStringContainsString( 'valid email', $response['data']['message'] );
			throw $e;
		}
	}

	/**
	 * Test that AJAX handler requires email parameter.
	 *
	 * @return void
	 */
	public function test_send_test_email_requires_email_param() {
		$_POST['nonce'] = wp_create_nonce( 'enjinmel_smtp_send_test_email' );

		$this->expectException( 'WPAjaxDieContinueException' );

		try {
			$this->_handleAjax( 'enjinmel_smtp_send_test_email' );
		} catch ( WPAjaxDieContinueException $e ) {
			$response = json_decode( $this->_last_response, true );
			$this->assertFalse( $response['success'] );
			$this->assertArrayHasKey( 'data', $response );
			$this->assertArrayHasKey( 'message', $response['data'] );
			throw $e;
		}
	}

	/**
	 * Test successful email send response.
	 *
	 * @return void
	 */
	public function test_send_test_email_success_response() {
		$_POST['nonce'] = wp_create_nonce( 'enjinmel_smtp_send_test_email' );
		$_POST['to']    = 'success@example.com';

		add_filter( 'pre_wp_mail', function() {
			return true;
		}, 1 );

		$this->expectException( 'WPAjaxDieContinueException' );

		try {
			$this->_handleAjax( 'enjinmel_smtp_send_test_email' );
		} catch ( WPAjaxDieContinueException $e ) {
			$response = json_decode( $this->_last_response, true );
			$this->assertTrue( $response['success'] );
			$this->assertArrayHasKey( 'data', $response );
			$this->assertArrayHasKey( 'message', $response['data'] );
			$this->assertStringContainsString( 'successfully', $response['data']['message'] );
			throw $e;
		}
	}

	/**
	 * Test failed email send response.
	 *
	 * @return void
	 */
	public function test_send_test_email_failure_response() {
		$_POST['nonce'] = wp_create_nonce( 'enjinmel_smtp_send_test_email' );
		$_POST['to']    = 'fail@example.com';

		add_filter( 'pre_wp_mail', function() {
			return new WP_Error( 'test_error', 'Test error message' );
		}, 1 );

		$this->expectException( 'WPAjaxDieContinueException' );

		try {
			$this->_handleAjax( 'enjinmel_smtp_send_test_email' );
		} catch ( WPAjaxDieContinueException $e ) {
			$response = json_decode( $this->_last_response, true );
			$this->assertFalse( $response['success'] );
			$this->assertArrayHasKey( 'data', $response );
			$this->assertArrayHasKey( 'message', $response['data'] );
			$this->assertStringContainsString( 'Test error message', $response['data']['message'] );
			throw $e;
		}
	}

	/**
	 * Test that email sanitization strips malicious content.
	 *
	 * @return void
	 */
	public function test_send_test_email_sanitizes_input() {
		$_POST['nonce'] = wp_create_nonce( 'enjinmel_smtp_send_test_email' );
		$_POST['to']    = '<script>alert("xss")</script>valid@example.com';

		add_filter( 'pre_wp_mail', function( $null, $args ) {
			$this->assertEquals( 'valid@example.com', $args['to'] );
			return true;
		}, 10, 2 );

		$this->expectException( 'WPAjaxDieContinueException' );

		try {
			$this->_handleAjax( 'enjinmel_smtp_send_test_email' );
		} catch ( WPAjaxDieContinueException $e ) {
			throw $e;
		}
	}

	/**
	 * Test that test email contains expected subject and content.
	 *
	 * @return void
	 */
	public function test_send_test_email_content() {
		$_POST['nonce'] = wp_create_nonce( 'enjinmel_smtp_send_test_email' );
		$_POST['to']    = 'content-test@example.com';

		$captured_args = null;
		add_filter( 'pre_wp_mail', function( $null, $args ) use ( &$captured_args ) {
			$captured_args = $args;
			return true;
		}, 10, 2 );

		$this->expectException( 'WPAjaxDieContinueException' );

		try {
			$this->_handleAjax( 'enjinmel_smtp_send_test_email' );
		} catch ( WPAjaxDieContinueException $e ) {
			$this->assertNotNull( $captured_args );
			$this->assertEquals( 'content-test@example.com', $captured_args['to'] );
			$this->assertStringContainsString( 'Test Email', $captured_args['subject'] );
			$this->assertStringContainsString( 'test email', $captured_args['message'] );
			throw $e;
		}
	}
}
