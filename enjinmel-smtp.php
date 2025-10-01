<?php
/**
 * Plugin Name: EnjinMel SMTP
 * Plugin URI:  https://github.com/enjinmel/enjinmel-smtp
 * Description: Replaces the default WordPress email sending functionality with EnjinMel SMTP for enhanced deliverability and reliability.
 * Version:     1.0.0
 * Author:      EnjinMel Contributors
 * Author URI:  https://github.com/enjinmel
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: enjinmel-smtp
 *
 * @package EnjinMel_SMTP
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-enjinmel-smtp-settings-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-enjinmel-smtp-encryption.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-enjinmel-smtp-api-client.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-enjinmel-smtp-logging.php';

/**
 * Return the plugin settings option key.
 *
 * @return string
 */
function enjinmel_smtp_option_key() {
	return 'enjinmel_smtp_settings';
}

/**
 * Return the legacy option key used before the rename.
 *
 * @return string
 */
function enjinmel_smtp_legacy_option_key() {
	return 'enginemail_smtp_settings';
}

/**
 * Return the settings group slug registered with WordPress.
 *
 * @return string
 */
function enjinmel_smtp_settings_group() {
	return 'enjinmel_smtp';
}

/**
 * Return the legacy settings group slug.
 *
 * @return string
 */
function enjinmel_smtp_legacy_settings_group() {
	return 'enginemail_smtp';
}

/**
 * Return the cron hook name used for log retention.
 *
 * @return string
 */
function enjinmel_smtp_cron_hook() {
	return 'enjinmel_smtp_retention_daily';
}

/**
 * Return the legacy cron hook used prior to the rename.
 *
 * @return string
 */
function enjinmel_smtp_legacy_cron_hook() {
	return 'enginemail_smtp_purge_logs';
}

/**
 * Return the log table name for the plugin.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @return string
 */
function enjinmel_smtp_log_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'enjinmel_smtp_logs';
}

/**
 * Return the legacy log table name.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @return string
 */
function enjinmel_smtp_legacy_log_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'enginemail_smtp_logs';
}

/**
 * Determine if a specific table exists.
 *
 * @global wpdb $wpdb
 * @param string $table Table name to inspect.
 * @return bool
 */
function enjinmel_smtp_table_exists( $table ) {
	global $wpdb;
	return ( $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) );
}

/**
 * Resolve the active log table, preferring the renamed table.
 *
 * @return string
 */
function enjinmel_smtp_active_log_table() {
	$new    = enjinmel_smtp_log_table_name();
	$legacy = enjinmel_smtp_legacy_log_table_name();

	if ( enjinmel_smtp_table_exists( $new ) ) {
		return $new;
	}

	if ( enjinmel_smtp_table_exists( $legacy ) ) {
		return $legacy;
	}

	return $new;
}

/**
 * Retrieve plugin settings with legacy fallback.
 *
 * @param array $default Default value when no settings are found.
 * @return array
 */
function enjinmel_smtp_get_settings( $default = array() ) {
	$settings = get_option( enjinmel_smtp_option_key(), null );
	if ( null === $settings ) {
		$settings = get_option( enjinmel_smtp_legacy_option_key(), null );
	}

	if ( ! is_array( $settings ) ) {
		return $default;
	}

	return $settings;
}

/**
 * Persist plugin settings.
 *
 * @param array $settings Sanitized settings array.
 * @return void
 */
function enjinmel_smtp_update_settings( array $settings ) {
	update_option( enjinmel_smtp_option_key(), $settings );
}

/**
 * Retrieve a specific setting key with legacy fallback.
 *
 * @param string $key     Settings key.
 * @param mixed  $default Default value when key is missing.
 * @return mixed
 */
function enjinmel_smtp_get_setting( $key, $default = null ) {
	$settings = enjinmel_smtp_get_settings();
	if ( isset( $settings[ $key ] ) ) {
		return $settings[ $key ];
	}

	$legacy = get_option( enjinmel_smtp_legacy_option_key(), array() );
	if ( is_array( $legacy ) && array_key_exists( $key, $legacy ) ) {
		return $legacy[ $key ];
	}

	return $default;
}

/**
 * Fetch a secret constant, supporting legacy names.
 *
 * @param string $constant     New constant name.
 * @param string $legacy_const Legacy constant name.
 * @return string|null
 */
function enjinmel_smtp_get_secret_constant( $constant, $legacy_const ) {
	if ( defined( $constant ) && '' !== constant( $constant ) ) {
		return constant( $constant );
	}

	if ( defined( $legacy_const ) && '' !== constant( $legacy_const ) ) {
		return constant( $legacy_const );
	}

	return null;
}

/**
 * Copy legacy settings to the new option name when the new option is empty.
 *
 * @return void
 */
function enjinmel_smtp_maybe_migrate_settings() {
	$new    = get_option( enjinmel_smtp_option_key(), null );
	$legacy = get_option( enjinmel_smtp_legacy_option_key(), null );

	if ( null === $new && is_array( $legacy ) ) {
		enjinmel_smtp_update_settings( $legacy );
	}
}

/**
 * Copy rows from the legacy log table into the renamed table the first time it is created.
 *
 * @return void
 */
function enjinmel_smtp_maybe_migrate_logs() {
	global $wpdb;

	$new_table    = enjinmel_smtp_log_table_name();
	$legacy_table = enjinmel_smtp_legacy_log_table_name();

	if ( $new_table === $legacy_table ) {
		return;
	}

	if ( ! enjinmel_smtp_table_exists( $new_table ) || ! enjinmel_smtp_table_exists( $legacy_table ) ) {
		return;
	}

	$has_rows = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$new_table}" );
	if ( $has_rows > 0 ) {
		return;
	}

	$wpdb->query( "INSERT INTO {$new_table} (timestamp, to_email, subject, status, error_message) SELECT timestamp, to_email, subject, status, error_message FROM {$legacy_table}" );
}

/**
 * Ensure the legacy settings URL redirects to the new slug.
 *
 * @return void
 */
function enjinmel_smtp_redirect_legacy_settings() {
	if ( isset( $_GET['page'] ) && enjinmel_sanitize_query_arg( wp_unslash( $_GET['page'] ) ) === enjinmel_smtp_legacy_settings_group() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_safe_redirect( admin_url( 'options-general.php?page=' . enjinmel_smtp_settings_group() ) );
		exit;
	}
}
add_action( 'admin_init', 'enjinmel_smtp_redirect_legacy_settings', 5 );

/**
 * Basic sanitization for query args used in redirects.
 *
 * @param string $value Raw value.
 * @return string
 */
function enjinmel_sanitize_query_arg( $value ) {
	return sanitize_key( $value );
}

/**
 * Create a WP_Error that includes a legacy error code for backwards compatibility.
 *
 * @param string     $code        New error code.
 * @param string     $message     Error message.
 * @param mixed      $data        Optional error data.
 * @param string|nil $legacy_code Legacy error code to append.
 * @return WP_Error
 */
function enjinmel_smtp_wp_error( $code, $message, $data = null, $legacy_code = null ) {
	$error = new WP_Error();
	$error->add( $code, $message, $data );
	if ( null !== $legacy_code ) {
		$error->add( $legacy_code, $message, $data );
	}
	return $error;
}

/**
 * Load plugin translations for both new and legacy text domains.
 *
 * @return void
 */
function enjinmel_smtp_load_textdomains() {
	$locale_path = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	load_plugin_textdomain( 'enjinmel-smtp', false, $locale_path );
	load_plugin_textdomain( 'enginemail-smtp', false, $locale_path );
}
add_action( 'plugins_loaded', 'enjinmel_smtp_load_textdomains', 0 );

add_action(
	'plugins_loaded',
	static function () {
		enjinmel_smtp_maybe_migrate_settings();
		enjinmel_smtp_maybe_migrate_logs();
	}
);

/**
 * Add the settings page to the admin menu.
 */
function enjinmel_smtp_add_admin_menu() {
	add_options_page(
		'EnjinMel SMTP',
		'EnjinMel SMTP',
		'manage_options',
		enjinmel_smtp_settings_group(),
		'enjinmel_smtp_options_page'
	);
}
add_action( 'admin_menu', 'enjinmel_smtp_add_admin_menu' );

/**
 * Register the settings.
 */
function enjinmel_smtp_settings_init() {
	register_setting(
		enjinmel_smtp_settings_group(),
		enjinmel_smtp_option_key(),
		array(
			'sanitize_callback' => 'enjinmel_smtp_settings_sanitize',
		)
	);

	// Allow the legacy settings group to continue saving without fatal errors.
	register_setting(
		enjinmel_smtp_legacy_settings_group(),
		enjinmel_smtp_legacy_option_key(),
		array(
			'sanitize_callback' => 'enjinmel_smtp_settings_sanitize_legacy',
		)
	);

	add_settings_section(
		'enjinmel_smtp_section',
		__( 'EnjinMel API Configuration', 'enjinmel-smtp' ),
		'enjinmel_smtp_section_callback',
		enjinmel_smtp_settings_group()
	);
}
add_action( 'admin_init', 'enjinmel_smtp_settings_init' );

/**
 * Section callback function.
 */
function enjinmel_smtp_section_callback() {
	echo esc_html__( 'Configure the EnjinMel REST API credentials and defaults used for transactional email.', 'enjinmel-smtp' );
}

/**
 * Render the settings page.
 */
function enjinmel_smtp_options_page() {
	?>
	<form action="options.php" method="post">
		<h2>EnjinMel SMTP</h2>
		<?php
		settings_fields( enjinmel_smtp_settings_group() );
		do_settings_sections( enjinmel_smtp_settings_group() );
		submit_button();
		?>
	</form>
	<hr />
	<h2><?php echo esc_html__( 'Send Test Email', 'enjinmel-smtp' ); ?></h2>
	<p>
		<label for="enjinmel_smtp_test_to"><?php echo esc_html__( 'Recipient', 'enjinmel-smtp' ); ?></label>
		<input type="email" id="enjinmel_smtp_test_to" placeholder="you@example.com" />
		<button class="button" id="enjinmel_smtp_send_test"><?php echo esc_html__( 'Send Test', 'enjinmel-smtp' ); ?></button>
		<input type="hidden" id="enjinmel_smtp_test_nonce" value="<?php echo esc_attr( wp_create_nonce( 'enjinmel_smtp_send_test_email' ) ); ?>" />
	</p>
	<p id="enjinmel_smtp_test_result"></p>
	<script type="text/javascript">
	(function($){
		$('#enjinmel_smtp_send_test').on('click', function(e){
			e.preventDefault();
			var to = $('#enjinmel_smtp_test_to').val();
			var nonce = $('#enjinmel_smtp_test_nonce').val();
			$('#enjinmel_smtp_test_result').text('<?php echo esc_js( __( 'Sending…', 'enjinmel-smtp' ) ); ?>');
			$.post(ajaxurl, {
				action: 'enjinmel_smtp_send_test_email',
				nonce: nonce,
				to: to
			}).done(function(resp){
				if(resp && resp.success){
					$('#enjinmel_smtp_test_result').text('<?php echo esc_js( __( 'Test email sent successfully.', 'enjinmel-smtp' ) ); ?>');
				} else {
					var msg = (resp && resp.data && resp.data.message) ? resp.data.message : '<?php echo esc_js( __( 'Failed to send test email.', 'enjinmel-smtp' ) ); ?>';
					$('#enjinmel_smtp_test_result').text('Error: ' + msg);
				}
			}).fail(function(){
				$('#enjinmel_smtp_test_result').text('<?php echo esc_js( __( 'Request failed. Please try again.', 'enjinmel-smtp' ) ); ?>');
			});
		});
	})(jQuery);
	</script>
	<?php
}

/**
 * Sanitize and persist settings; encrypt password if provided.
 *
 * @param array $input The raw settings input.
 * @return array Sanitized settings.
 */
function enjinmel_smtp_settings_sanitize( $input ) {
	$output   = array();
	$existing = enjinmel_smtp_get_settings( array() );

	$output['campaign_name'] = isset( $input['campaign_name'] ) ? sanitize_text_field( $input['campaign_name'] ) : '';
	$output['template_id']   = isset( $input['template_id'] ) ? sanitize_text_field( $input['template_id'] ) : '';
	$output['from_name']     = isset( $input['from_name'] ) ? sanitize_text_field( $input['from_name'] ) : '';
	$output['from_email']    = isset( $input['from_email'] ) ? sanitize_email( $input['from_email'] ) : '';
	$output['force_from']    = ! empty( $input['force_from'] ) ? 1 : 0;
	if ( array_key_exists( 'enable_logging', $input ) ) {
		$output['enable_logging'] = ! empty( $input['enable_logging'] ) ? 1 : 0;
	} elseif ( isset( $existing['enable_logging'] ) ) {
		$output['enable_logging'] = (int) $existing['enable_logging'];
	} else {
		$output['enable_logging'] = 1; // default enabled on first save.
	}

	if ( isset( $input['api_key'] ) && '' !== $input['api_key'] ) {
		$encrypted = EnjinMel_SMTP_Encryption::encrypt( trim( $input['api_key'] ) );
		if ( is_wp_error( $encrypted ) ) {
			add_settings_error( enjinmel_smtp_option_key(), 'enjinmel_api_key_encrypt', $encrypted->get_error_message(), 'error' );
			$output['api_key'] = isset( $existing['api_key'] ) ? $existing['api_key'] : '';
		} else {
			$output['api_key'] = $encrypted;
		}
	} else {
		$output['api_key'] = isset( $existing['api_key'] ) ? $existing['api_key'] : '';
	}

	return $output;
}

/**
 * Sanitize legacy settings submissions and migrate them to the new option key.
 *
 * @param array $input Raw settings payload from legacy form.
 * @return array
 */
function enjinmel_smtp_settings_sanitize_legacy( $input ) {
	$sanitized = enjinmel_smtp_settings_sanitize( $input );
	if ( ! empty( $sanitized ) ) {
		enjinmel_smtp_update_settings( $sanitized );
	}
	return $sanitized;
}

/**
 * Short-circuit wp_mail() to submit messages via the EnjinMel REST API.
 *
 * @param null|bool|WP_Error $preemptive_return Preemptive return value.
 * @param array              $args                Mail arguments.
 * @return bool|WP_Error
 */
function enjinmel_smtp_pre_wp_mail( $preemptive_return, $args ) {
	if ( null !== $preemptive_return && ! ( $preemptive_return instanceof WP_Error ) ) {
		return $preemptive_return;
	}

	$response = EnjinMel_SMTP_API_Client::send( $args );
	if ( is_wp_error( $response ) ) {
		$error = new WP_Error();
		$error->add(
			'wp_mail_failed',
			$response->get_error_message(),
			array(
				'to'           => $args['to'],
				'subject'      => $args['subject'],
				'message'      => $args['message'],
				'headers'      => $args['headers'],
				'attachments'  => $args['attachments'],
				'transport'    => 'enjinmel_rest',
				'legacy_transport' => 'enginemail_rest',
				'engine_error' => array(
					'code' => $response->get_error_code(),
					'data' => $response->get_error_data(),
				),
			)
		);

 		$error->add(
 			'enjinmel_rest_failure',
 			$response->get_error_message(),
 			$response->get_error_data()
 		);

		$error->add(
			'enginemail_rest_failure',
			$response->get_error_message(),
			$response->get_error_data()
		);

		// Preserve the specific provider error code for debugging.
		$error->add(
			$response->get_error_code(),
			$response->get_error_message(),
			$response->get_error_data()
		);

		/**
		 * Mirror core behaviour for failed mail to keep downstream hooks consistent.
		 */
		do_action( 'wp_mail_failed', $error );
		return $error;
	}

	/**
	 * Mirror core behaviour for successful mail sends.
	 */
	do_action(
		'wp_mail_succeeded',
		array(
			'to'          => $args['to'],
			'subject'     => $args['subject'],
			'message'     => $args['message'],
			'headers'     => $args['headers'],
			'attachments' => $args['attachments'],
			'transport'   => 'enjinmel_rest',
			'legacy_transport' => 'enginemail_rest',
		)
	);

	return true;
}
add_filter( 'pre_wp_mail', 'enjinmel_smtp_pre_wp_mail', 10, 2 );

// Initialize logging hooks.
if ( class_exists( 'EnjinMel_SMTP_Logging' ) ) {
	EnjinMel_SMTP_Logging::init();
}

/**
 * Plugin activation: create custom table for email logs.
 */
function enjinmel_smtp_activate() {
	global $wpdb;

	$table_name      = enjinmel_smtp_log_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = "CREATE TABLE {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        timestamp DATETIME NOT NULL,
        to_email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        status VARCHAR(20) NOT NULL,
        error_message TEXT,
        PRIMARY KEY  (id),
        KEY timestamp (timestamp)
    ) {$charset_collate};";

	dbDelta( $sql );

	enjinmel_smtp_maybe_migrate_settings();
	enjinmel_smtp_maybe_migrate_logs();

	if ( class_exists( 'EnjinMel_SMTP_Logging' ) ) {
		EnjinMel_SMTP_Logging::schedule_events();
	}
}

register_activation_hook( __FILE__, 'enjinmel_smtp_activate' );

/**
 * Plugin deactivation: clear scheduled events.
 */
function enjinmel_smtp_deactivate() {
	if ( class_exists( 'EnjinMel_SMTP_Logging' ) ) {
		EnjinMel_SMTP_Logging::unschedule_events();
	}
}

register_deactivation_hook( __FILE__, 'enjinmel_smtp_deactivate' );

/**
 * Surface admin notices for missing encryption constants or API key issues.
 */
function enjinmel_smtp_admin_notices() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$key = enjinmel_smtp_get_secret_constant( 'ENJINMEL_SMTP_KEY', 'ENGINEMAIL_SMTP_KEY' );
	$iv  = enjinmel_smtp_get_secret_constant( 'ENJINMEL_SMTP_IV', 'ENGINEMAIL_SMTP_IV' );

	if ( null === $key || null === $iv ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'EnjinMel SMTP: define ENJINMEL_SMTP_KEY and ENJINMEL_SMTP_IV (legacy ENGINEMAIL_* constants are still accepted) in wp-config.php to secure stored credentials.', 'enjinmel-smtp' ) . '</p></div>';
		return;
	}

	$settings = enjinmel_smtp_get_settings( array() );
	if ( empty( $settings['api_key'] ) ) {
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'EnjinMel SMTP: add your API key to enable transactional email sends.', 'enjinmel-smtp' ) . '</p></div>';
		return;
	}

	$api_key = EnjinMel_SMTP_Encryption::decrypt( $settings['api_key'] );
	if ( is_wp_error( $api_key ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $api_key->get_error_message() ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'enjinmel_smtp_admin_notices' );
