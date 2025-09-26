<?php
/**
 * Plugin Name: EngineMail SMTP
 * Plugin URI:  https://enginemail.com
 * Description: Replaces the default WordPress email sending functionality with EngineMail SMTP for enhanced deliverability and reliability.
 * Version:     1.0.0
 * Author:      EngineMail
 * Author URI:  https://enginemail.com
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: enginemail-smtp
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/settings-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-encryption.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-api-client.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-logging.php';

// Add the settings page to the admin menu.
function enginemail_smtp_add_admin_menu() {
    add_options_page(
        'EngineMail SMTP',
        'EngineMail SMTP',
        'manage_options',
        'enginemail_smtp',
        'enginemail_smtp_options_page'
    );
}
add_action( 'admin_menu', 'enginemail_smtp_add_admin_menu' );

// Register the settings.
function enginemail_smtp_settings_init() {
    register_setting(
        'enginemail_smtp',
        'enginemail_smtp_settings',
        array(
            'sanitize_callback' => 'enginemail_smtp_settings_sanitize',
        )
    );

    add_settings_section(
        'enginemail_smtp_section',
        __( 'EngineMail API Configuration', 'enginemail-smtp' ),
        'enginemail_smtp_section_callback',
        'enginemail_smtp'
    );
}
add_action( 'admin_init', 'enginemail_smtp_settings_init' );

// Section callback function.
function enginemail_smtp_section_callback() {
    echo __( 'Configure the EngineMail REST API credentials and defaults used for transactional email.', 'enginemail-smtp' );
}

// Render the settings page.
function enginemail_smtp_options_page() {
    ?>
    <form action="options.php" method="post">
        <h2>EngineMail SMTP</h2>
        <?php
        settings_fields( 'enginemail_smtp' );
        do_settings_sections( 'enginemail_smtp' );
        submit_button();
        ?>
    </form>
    <hr />
    <h2><?php echo esc_html__( 'Send Test Email', 'enginemail-smtp' ); ?></h2>
    <p>
        <label for="enginemail_smtp_test_to"><?php echo esc_html__( 'Recipient', 'enginemail-smtp' ); ?></label>
        <input type="email" id="enginemail_smtp_test_to" placeholder="you@example.com" />
        <button class="button" id="enginemail_smtp_send_test"><?php echo esc_html__( 'Send Test', 'enginemail-smtp' ); ?></button>
        <input type="hidden" id="enginemail_smtp_test_nonce" value="<?php echo esc_attr( wp_create_nonce( 'enginemail_smtp_send_test_email' ) ); ?>" />
    </p>
    <p id="enginemail_smtp_test_result"></p>
    <script type="text/javascript">
    (function($){
        $('#enginemail_smtp_send_test').on('click', function(e){
            e.preventDefault();
            var to = $('#enginemail_smtp_test_to').val();
            var nonce = $('#enginemail_smtp_test_nonce').val();
            $('#enginemail_smtp_test_result').text('<?php echo esc_js( __( 'Sending…', 'enginemail-smtp' ) ); ?>');
            $.post(ajaxurl, {
                action: 'enginemail_smtp_send_test_email',
                nonce: nonce,
                to: to
            }).done(function(resp){
                if(resp && resp.success){
                    $('#enginemail_smtp_test_result').text('<?php echo esc_js( __( 'Test email sent successfully.', 'enginemail-smtp' ) ); ?>');
                } else {
                    var msg = (resp && resp.data && resp.data.message) ? resp.data.message : '<?php echo esc_js( __( 'Failed to send test email.', 'enginemail-smtp' ) ); ?>';
                    $('#enginemail_smtp_test_result').text('Error: ' + msg);
                }
            }).fail(function(){
                $('#enginemail_smtp_test_result').text('<?php echo esc_js( __( 'Request failed. Please try again.', 'enginemail-smtp' ) ); ?>');
            });
        });
    })(jQuery);
    </script>
    <?php
}

/**
 * Sanitize and persist settings; encrypt password if provided.
 */
function enginemail_smtp_settings_sanitize( $input ) {
    $output   = array();
    $existing = get_option( 'enginemail_smtp_settings', array() );

    $output['campaign_name'] = isset( $input['campaign_name'] ) ? sanitize_text_field( $input['campaign_name'] ) : '';
    $output['template_id']   = isset( $input['template_id'] ) ? sanitize_text_field( $input['template_id'] ) : '';
    $output['from_name']     = isset( $input['from_name'] ) ? sanitize_text_field( $input['from_name'] ) : '';
    $output['from_email']    = isset( $input['from_email'] ) ? sanitize_email( $input['from_email'] ) : '';
    $output['force_from'] = ! empty( $input['force_from'] ) ? 1 : 0;
    if ( array_key_exists( 'enable_logging', $input ) ) {
        $output['enable_logging'] = ! empty( $input['enable_logging'] ) ? 1 : 0;
    } elseif ( isset( $existing['enable_logging'] ) ) {
        $output['enable_logging'] = (int) $existing['enable_logging'];
    } else {
        $output['enable_logging'] = 1; // default enabled on first save.
    }

    if ( isset( $input['api_key'] ) && '' !== $input['api_key'] ) {
        $encrypted = EngineMail_SMTP_Encryption::encrypt( trim( $input['api_key'] ) );
        if ( is_wp_error( $encrypted ) ) {
            add_settings_error( 'enginemail_smtp_settings', 'enginemail_api_key_encrypt', $encrypted->get_error_message(), 'error' );
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
 * Short-circuit wp_mail() to submit messages via EngineMail REST API.
 *
 * @param null|bool|WP_Error $return Preemptive return value.
 * @param array              $args   Mail arguments.
 * @return bool|WP_Error
 */
function enginemail_smtp_pre_wp_mail( $return, $args ) {
    if ( null !== $return && ! ( $return instanceof WP_Error ) ) {
        return $return;
    }

    $response = EngineMail_SMTP_API_Client::send( $args );
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
                'transport'   => 'enginemail_rest',
                'engine_error' => array(
                    'code' => $response->get_error_code(),
                    'data' => $response->get_error_data(),
                ),
            )
        );

        // Preserve the specific EngineMail error code for debugging.
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
    do_action( 'wp_mail_succeeded', array(
        'to'          => $args['to'],
        'subject'     => $args['subject'],
        'message'     => $args['message'],
        'headers'     => $args['headers'],
        'attachments' => $args['attachments'],
        'transport'   => 'enginemail_rest',
    ) );

    return true;
}
add_filter( 'pre_wp_mail', 'enginemail_smtp_pre_wp_mail', 10, 2 );

// Initialize logging hooks.
if ( class_exists( 'EngineMail_SMTP_Logging' ) ) {
    EngineMail_SMTP_Logging::init();
}

/**
 * Plugin activation: create custom table for email logs.
 */
function enginemail_smtp_activate() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'enginemail_smtp_logs';
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
}

register_activation_hook( __FILE__, 'enginemail_smtp_activate' );

/**
 * Surface admin notices for missing encryption constants or API key issues.
 */
function enginemail_smtp_admin_notices() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( ! defined( 'ENGINEMAIL_SMTP_KEY' ) || ! defined( 'ENGINEMAIL_SMTP_IV' ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'EngineMail: define ENGINEMAIL_SMTP_KEY and ENGINEMAIL_SMTP_IV in wp-config.php to secure stored credentials.', 'enginemail-smtp' ) . '</p></div>';
        return;
    }

    $settings = get_option( 'enginemail_smtp_settings', array() );
    if ( empty( $settings['api_key'] ) ) {
        echo '<div class="notice notice-warning"><p>' . esc_html__( 'EngineMail: add your EngineMail API key to enable transactional email sends.', 'enginemail-smtp' ) . '</p></div>';
        return;
    }

    $api_key = EngineMail_SMTP_Encryption::decrypt( $settings['api_key'] );
    if ( is_wp_error( $api_key ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html( $api_key->get_error_message() ) . '</p></div>';
    }
}
add_action( 'admin_notices', 'enginemail_smtp_admin_notices' );
