<?php
/**
 * Plugin Name: EnjinMel SMTP
 * Plugin URI:  https://github.com/liewcf/enjinmel-smtp
 * Description: Replaces the default WordPress email sending functionality with Enginemailer API key for enhanced deliverability and reliability.
 * Version:     0.1.0
 * Author:      Liew CheonFong
 * Author URI:  https://github.com/liewcf
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: enjinmel-smtp
 * Domain Path: /languages
 * Requires at least: 5.3
 * Requires PHP: 7.4
 * Tested up to: 6.8.3
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
require_once plugin_dir_path( __FILE__ ) . 'includes/class-enjinmel-smtp-log-viewer.php';

/**
 * Return the plugin settings option key.
 *
 * @return string
 */
function enjinmel_smtp_option_key() {
	return 'enjinmel_smtp_settings';
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
 * Return the cron hook name used for log retention.
 *
 * @return string
 */
function enjinmel_smtp_cron_hook() {
	return 'enjinmel_smtp_retention_daily';
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
 * Determine if a specific table exists.
 *
 * @global wpdb $wpdb
 * @param  string $table Table name to inspect.
 * @return bool
 */
function enjinmel_smtp_table_exists( $table ) {
	global $wpdb;
	return ( $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Single table existence check executed during administrative flows.
}

/**
 * Resolve the active log table.
 *
 * @return string
 */
function enjinmel_smtp_active_log_table() {
	return enjinmel_smtp_log_table_name();
}

/**
 * Sanitize a table name before interpolating into queries.
 *
 * @param  string $table Raw table name.
 * @return string
 */
function enjinmel_smtp_sanitize_table_name( $table ) {
	return preg_replace( '/[^A-Za-z0-9_]/', '', (string) $table );
}

/**
 * Retrieve plugin settings.
 *
 * @param  array $default_value Default value when no settings are found.
 * @return array
 */
function enjinmel_smtp_get_settings( $default_value = array() ) {
	$settings = get_option( enjinmel_smtp_option_key(), $default_value );

	if ( ! is_array( $settings ) ) {
		return $default_value;
	}

	return $settings;
}

/**
 * Persist plugin settings.
 *
 * @param  array $settings Sanitized settings array.
 * @return void
 */
function enjinmel_smtp_update_settings( array $settings ) {
	update_option( enjinmel_smtp_option_key(), $settings );
}

/**
 * Retrieve a specific setting key.
 *
 * @param  string $key           Settings key.
 * @param  mixed  $default_value Default value when key is missing.
 * @return mixed
 */
function enjinmel_smtp_get_setting( $key, $default_value = null ) {
	$settings = enjinmel_smtp_get_settings();
	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default_value;
}





/**
 * Basic sanitization for query args used in redirects.
 *
 * @param  string $value Raw value.
 * @return string
 */
function enjinmel_sanitize_query_arg( $value ) {
	return sanitize_key( $value );
}

/**
 * Load plugin translations.
 *
 * @return void
 */
function enjinmel_smtp_load_textdomains() {
	$locale_path = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	load_plugin_textdomain( 'enjinmel-smtp', false, $locale_path );
}
add_action( 'plugins_loaded', 'enjinmel_smtp_load_textdomains', 0 );

/**
 * Add the settings page to the admin menu.
 */
function enjinmel_smtp_add_admin_menu() {
	add_menu_page(
		'EnjinMel SMTP',
		'EnjinMel SMTP',
		'manage_options',
		enjinmel_smtp_settings_group(),
		'enjinmel_smtp_options_page',
		'dashicons-email',
		30
	);

	// Add settings submenu with same slug as parent to avoid duplicate menu item.
	add_submenu_page(
		enjinmel_smtp_settings_group(),
		'Settings',
		'Settings',
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
	printf(
		/* translators: %s: link to API key management page */
		esc_html__( 'Configure the Enginemailer %s and defaults used for transactional email.', 'enjinmel-smtp' ),
		'<a href="https://portal.enginemailer.com/Account/APIs" target="_blank" rel="noopener noreferrer">' . esc_html__( 'API Key', 'enjinmel-smtp' ) . '</a>'
	);
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
					$('#enjinmel_smtp_test_result').html('<strong style="color: #46b450;"><?php echo esc_js( __( 'Test email sent successfully.', 'enjinmel-smtp' ) ); ?></strong>');
				} else {
					var msg = (resp && resp.data && resp.data.message) ? resp.data.message : '<?php echo esc_js( __( 'Failed to send test email.', 'enjinmel-smtp' ) ); ?>';
					$('#enjinmel_smtp_test_result').html('<span style="color: #dc3232;">Error: ' + msg + '</span>');
				}
			}).fail(function(){
				$('#enjinmel_smtp_test_result').html('<span style="color: #dc3232;"><?php echo esc_js( __( 'Request failed. Please try again.', 'enjinmel-smtp' ) ); ?></span>');
			});
		});
	})(jQuery);
	</script>
	<?php
}

/**
 * Sanitize and persist settings; encrypt password if provided.
 *
 * @param  array $input The raw settings input.
 * @return array Sanitized settings.
 */
function enjinmel_smtp_settings_sanitize( $input ) {
	$output   = array();
	$existing = enjinmel_smtp_get_settings( array() );

	$output['campaign_name']  = isset( $input['campaign_name'] ) ? sanitize_text_field( $input['campaign_name'] ) : '';
	$output['template_id']    = isset( $input['template_id'] ) ? sanitize_text_field( $input['template_id'] ) : '';
	$output['from_name']      = isset( $input['from_name'] ) ? sanitize_text_field( $input['from_name'] ) : '';
	
	// Validate and sanitize from_email.
	$raw_email = isset( $input['from_email'] ) ? trim( (string) $input['from_email'] ) : '';
	if ( $raw_email !== '' && ! is_email( $raw_email ) ) {
		add_settings_error(
			enjinmel_smtp_option_key(),
			'enjinmel_invalid_from_email',
			__( 'The sender email address is invalid. Please enter a valid email address.', 'enjinmel-smtp' ),
			'error'
		);
		// Preserve existing valid email on validation failure.
		$output['from_email'] = isset( $existing['from_email'] ) ? $existing['from_email'] : '';
	} else {
		// Empty allowed (to clear), sanitize valid emails.
		$output['from_email'] = sanitize_email( $raw_email );
	}
	
	$output['force_from'] = ! empty( $input['force_from'] ) ? 1 : 0;
	
	// Default logging to enabled (1) if not explicitly set.
	if ( isset( $input['enable_logging'] ) ) {
		$output['enable_logging'] = ! empty( $input['enable_logging'] ) ? 1 : 0;
	} else {
		// Preserve existing value or default to enabled.
		$output['enable_logging'] = isset( $existing['enable_logging'] ) ? $existing['enable_logging'] : 1;
	}

	$submitted_key = isset( $input['api_key'] ) ? trim( (string) $input['api_key'] ) : '';
	if ( '' !== $submitted_key ) {

		// Check if the submitted value is a masked key (contains asterisks).
		if ( strpos( $submitted_key, '*' ) !== false ) {
			// Keep the existing encrypted key.
			$output['api_key'] = isset( $existing['api_key'] ) ? $existing['api_key'] : '';
		} else {
			// New API key provided, encrypt it.
			$encrypted = EnjinMel_SMTP_Encryption::encrypt( $submitted_key );
			if ( is_wp_error( $encrypted ) ) {
				add_settings_error( enjinmel_smtp_option_key(), 'enjinmel_api_key_encrypt', $encrypted->get_error_message(), 'error' );
				$output['api_key'] = isset( $existing['api_key'] ) ? $existing['api_key'] : '';
			} else {
				$output['api_key'] = $encrypted;
			}
		}
	} else {
		$output['api_key'] = isset( $existing['api_key'] ) ? $existing['api_key'] : '';
	}

	return $output;
}

/**
 * Short-circuit wp_mail() to submit messages via the Enginemailer REST API.
 *
 * @param  null|bool|WP_Error $preemptive_return Preemptive return value.
 * @param  array              $args              Mail arguments.
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
				'to'          => $args['to'],
				'subject'     => $args['subject'],
				'message'     => $args['message'],
				'headers'     => $args['headers'],
				'attachments' => $args['attachments'],
				'transport'   => 'enjinmel_rest',
			)
		);

		$error->add(
			'enjinmel_rest_failure',
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
		)
	);

	return true;
}
add_filter( 'pre_wp_mail', 'enjinmel_smtp_pre_wp_mail', 10, 2 );

// Initialize logging hooks.
if ( class_exists( 'EnjinMel_SMTP_Logging' ) ) {
	EnjinMel_SMTP_Logging::init();
}

// Initialize log viewer.
if ( class_exists( 'EnjinMel_SMTP_Log_Viewer' ) ) {
	EnjinMel_SMTP_Log_Viewer::init();
}

/**
 * Plugin activation: create custom table for email logs.
 */
function enjinmel_smtp_activate() {
	global $wpdb;

	$table_name      = enjinmel_smtp_log_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	include_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = "CREATE TABLE {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        timestamp DATETIME NOT NULL,
        to_email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        status VARCHAR(20) NOT NULL,
        error_message TEXT,
        PRIMARY KEY  (id),
        KEY timestamp (timestamp),
        KEY status_ts (status, timestamp)
    ) {$charset_collate};";

	dbDelta( $sql );

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
 * Surface admin notices for API key issues.
 */
function enjinmel_smtp_admin_notices() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = enjinmel_smtp_get_settings( array() );
	if ( empty( $settings['api_key'] ) ) {
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'EnjinMel SMTP: add your API key in the settings page to enable transactional email sends.', 'enjinmel-smtp' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=' . enjinmel_smtp_settings_group() ) ) . '">' . esc_html__( 'Configure Now', 'enjinmel-smtp' ) . '</a></p></div>';
		return;
	}

	$api_key = EnjinMel_SMTP_Encryption::decrypt( $settings['api_key'] );
	if ( is_wp_error( $api_key ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'EnjinMel SMTP: ', 'enjinmel-smtp' ) . esc_html( $api_key->get_error_message() ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'enjinmel_smtp_admin_notices' );
