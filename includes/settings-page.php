<?php
// Add the settings fields for REST configuration.
function enginemail_smtp_add_settings_fields() {
    add_settings_field(
        'enginemail_smtp_api_key',
        __( 'API Key', 'enginemail-smtp' ),
        'enginemail_smtp_api_key_render',
        'enginemail_smtp',
        'enginemail_smtp_section'
    );

    add_settings_field(
        'enginemail_smtp_campaign_name',
        __( 'Default Campaign Name', 'enginemail-smtp' ),
        'enginemail_smtp_campaign_name_render',
        'enginemail_smtp',
        'enginemail_smtp_section'
    );

    add_settings_field(
        'enginemail_smtp_template_id',
        __( 'Template ID (optional)', 'enginemail-smtp' ),
        'enginemail_smtp_template_id_render',
        'enginemail_smtp',
        'enginemail_smtp_section'
    );

    add_settings_field(
        'enginemail_smtp_from_name',
        __( 'Sender Name', 'enginemail-smtp' ),
        'enginemail_smtp_from_name_render',
        'enginemail_smtp',
        'enginemail_smtp_section'
    );

    add_settings_field(
        'enginemail_smtp_from_email',
        __( 'Sender Email', 'enginemail-smtp' ),
        'enginemail_smtp_from_email_render',
        'enginemail_smtp',
        'enginemail_smtp_section'
    );

    add_settings_field(
        'enginemail_smtp_force_from',
        __( 'Force Sender', 'enginemail-smtp' ),
        'enginemail_smtp_force_from_render',
        'enginemail_smtp',
        'enginemail_smtp_section'
    );

    add_settings_field(
        'enginemail_smtp_enable_logging',
        __( 'Enable Logging', 'enginemail-smtp' ),
        'enginemail_smtp_enable_logging_render',
        'enginemail_smtp',
        'enginemail_smtp_section'
    );
}
add_action( 'admin_init', 'enginemail_smtp_add_settings_fields' );

// Render the fields.
function enginemail_smtp_api_key_render() {
    $options = get_option( 'enginemail_smtp_settings' );
    ?>
    <input type='password' autocomplete='off' name='enginemail_smtp_settings[api_key]' value='' placeholder='<?php echo esc_attr__( 'Enter API key (leave blank to keep unchanged)', 'enginemail-smtp' ); ?>'>
    <?php
}

function enginemail_smtp_campaign_name_render() {
    $options = get_option( 'enginemail_smtp_settings' );
    ?>
    <input type='text' name='enginemail_smtp_settings[campaign_name]' value='<?php echo isset( $options['campaign_name'] ) ? esc_attr( $options['campaign_name'] ) : ''; ?>' placeholder='<?php echo esc_attr__( 'Transactional Campaign', 'enginemail-smtp' ); ?>'>
    <?php
}

function enginemail_smtp_template_id_render() {
    $options = get_option( 'enginemail_smtp_settings' );
    ?>
    <input type='text' name='enginemail_smtp_settings[template_id]' value='<?php echo isset( $options['template_id'] ) ? esc_attr( $options['template_id'] ) : ''; ?>' placeholder='<?php echo esc_attr__( 'e.g. TPL-123', 'enginemail-smtp' ); ?>'>
    <?php
}

function enginemail_smtp_from_name_render() {
    $options = get_option( 'enginemail_smtp_settings' );
    ?>
    <input type='text' name='enginemail_smtp_settings[from_name]' value='<?php echo isset( $options['from_name'] ) ? esc_attr( $options['from_name'] ) : ''; ?>' placeholder='<?php echo esc_attr__( 'EngineMail', 'enginemail-smtp' ); ?>'>
    <?php
}

function enginemail_smtp_from_email_render() {
    $options = get_option( 'enginemail_smtp_settings' );
    ?>
    <input type='email' name='enginemail_smtp_settings[from_email]' value='<?php echo isset( $options['from_email'] ) ? esc_attr( $options['from_email'] ) : ''; ?>' placeholder='noreply@example.com'>
    <?php
}

function enginemail_smtp_force_from_render() {
    $options = get_option( 'enginemail_smtp_settings' );
    $checked = isset( $options['force_from'] ) ? (int) $options['force_from'] : 0;
    ?>
    <label>
        <input type='checkbox' name='enginemail_smtp_settings[force_from]' value='1' <?php checked( $checked, 1 ); ?>>
        <?php echo esc_html__( 'Always use the configured sender details', 'enginemail-smtp' ); ?>
    </label>
    <?php
}

function enginemail_smtp_enable_logging_render() {
    $options = get_option( 'enginemail_smtp_settings' );
    $checked = isset( $options['enable_logging'] ) ? (int) $options['enable_logging'] : 1;
    ?>
    <label>
        <input type='checkbox' name='enginemail_smtp_settings[enable_logging]' value='1' <?php checked( $checked, 1 ); ?>>
        <?php echo esc_html__( 'Store send results in the EngineMail log table', 'enginemail-smtp' ); ?>
    </label>
    <?php
}

// AJAX handler for Send Test Email (admin only)
function enginemail_smtp_send_test_email() {
    check_ajax_referer( 'enginemail_smtp_send_test_email', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'enginemail-smtp' ) ), 403 );
    }
    $to = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
    if ( empty( $to ) || ! is_email( $to ) ) {
        wp_send_json_error( array( 'message' => __( 'Please provide a valid email address.', 'enginemail-smtp' ) ), 400 );
    }

    $subject = __( 'EngineMail SMTP Test Email', 'enginemail-smtp' );
    $message = __( 'This is a test email sent from the EngineMail SMTP plugin settings page.', 'enginemail-smtp' );
    $sent    = wp_mail( $to, $subject, $message );

    if ( is_wp_error( $sent ) ) {
        wp_send_json_error( array( 'message' => $sent->get_error_message() ), 500 );
    }

    if ( true === $sent ) {
        wp_send_json_success( array( 'message' => __( 'Email sent successfully.', 'enginemail-smtp' ) ) );
    }

    wp_send_json_error( array( 'message' => __( 'EngineMail API did not confirm the send.', 'enginemail-smtp' ) ), 500 );
}
add_action( 'wp_ajax_enginemail_smtp_send_test_email', 'enginemail_smtp_send_test_email' );
