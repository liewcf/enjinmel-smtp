<?php
/**
 * PHPUnit bootstrap file for EnjinMel SMTP tests.
 *
 * @package EnjinMel_SMTP_Tests
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Load the plugin into the WordPress test environment.
 *
 * @return void
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/enjinmel-smtp.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
