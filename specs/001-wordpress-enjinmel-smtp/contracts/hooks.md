# Hooks for EnjinMel SMTP Plugin

> _Hooks formerly prefixed with `enginemail_smtp_`; the EnjinMel rename introduces new prefixes while keeping legacy ones for compatibility._

This document outlines the WordPress action and filter hooks that the plugin will use or expose.

## Used Hooks (Actions)

*   `phpmailer_init`: This is the primary hook used to configure PHPMailer to use the EnjinMel SMTP settings.
*   `admin_init`: Used to register the plugin's settings with the WordPress Settings API.
*   `admin_menu`: Used to add the plugin's settings page to the admin menu.
*   `register_activation_hook`: Used to call the function that creates the custom database table for logging.
*   `register_deactivation_hook`: Used to clean up any resources on deactivation (e.g., scheduled events).

## Exposed Hooks (Actions and Filters)

The plugin will expose the following hooks to allow other developers to extend its functionality.

*   `enjinmel_smtp_before_send` (Action): Fires just before the email is sent, after the PHPMailer object is configured. Passes the PHPMailer object as an argument.
*   `enjinmel_smtp_after_send` (Action): Fires after the email has been sent. Passes an array with the email details and status as an argument.
*   `enjinmel_smtp_log_entry` (Filter): Allows modification of the log entry data before it is saved to the database. Passes the log entry array as an argument.
