=== EnjinMel SMTP ===
Contributors: cheonfongliew
Tags: email, smtp, transactional email, wp_mail, email delivery
Requires at least: 5.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Replace WordPress default email sending with Enginemailer API for enhanced deliverability and reliability.

== Description ==

EnjinMel SMTP replaces the default WordPress email sending functionality with the powerful Enginemailer REST API to ensure your transactional emails are delivered reliably and efficiently.

**Important:** This plugin is an independent WordPress integration and is not affiliated with or endorsed by Enginemailer.

**Understanding the Names:**

* **EnjinMel SMTP** - The name of this WordPress plugin
* **Enginemailer** - The third-party email delivery API service (https://enginemailer.com)

This plugin connects your WordPress site to the Enginemailer service API.

**Key Features:**

* **Seamless Integration** - Automatically intercepts all `wp_mail()` calls
* **Enhanced Deliverability** - Routes emails through Enginemailer's reliable email infrastructure
* **Comprehensive Logging** - Track email sends with detailed logs including timestamps, recipients, and status
* **Test Email Functionality** - Verify your configuration by sending test emails
* **Secure API Key Storage** - API keys are encrypted using AES-256-CBC encryption
* **Log Management** - Automatic log retention with configurable cleanup schedules
* **Admin Interface** - User-friendly settings page with log viewer

**Perfect For:**

* Membership sites
* E-commerce platforms
* Contact forms
* Password reset emails
* Order confirmations
* User notifications

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/enjinmel-smtp/`, or install through the WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to EnjinMel SMTP settings page
4. Enter your Enginemailer API key (get one at https://portal.enginemailer.com/Account/APIs)
5. Configure your default From Name and From Email
6. Send a test email to verify everything works

**Optional:** For enhanced security, you can define custom encryption constants in your `wp-config.php` file. If not provided, the plugin will auto-generate and store encryption keys in the database:
```php
define('ENJINMEL_SMTP_KEY', 'your-32-character-key-here');
define('ENJINMEL_SMTP_IV', 'your-16-character-iv-here');
```

== Frequently Asked Questions ==

= Where do I get an API key? =

You can obtain an API key from the Enginemailer portal at https://portal.enginemailer.com/Account/APIs

= What are the encryption constants and why do I need them? =

The plugin uses AES-256-CBC encryption to securely store your API key in the database. By default, the plugin auto-generates and stores encryption keys in the database. For enhanced security in shared hosting or high-security environments, you can optionally define custom `ENJINMEL_SMTP_KEY` and `ENJINMEL_SMTP_IV` constants in your `wp-config.php` file.

= Can I view sent email logs? =

Yes! The plugin includes a comprehensive log viewer accessible from the WordPress admin menu. You can filter, search, and export email logs.

= How long are logs kept? =

By default, logs are kept for 90 days. The plugin automatically purges older logs via a daily cron job.

= Is this compatible with other email plugins? =

This plugin intercepts `wp_mail()` calls, so it will override other email plugins. Only activate one email sending plugin at a time.

= Does this work with WooCommerce, Contact Form 7, etc? =

Yes! Any plugin that uses WordPress's standard `wp_mail()` function will automatically use EnjinMel SMTP for email delivery.

== Screenshots ==

1. Settings page with API configuration
2. Log viewer with filtering and export options

== Changelog ==

= 0.1.0 =
* Initial release
* Enginemailer REST API integration
* Encrypted API key storage
* Email logging with retention management
* Admin log viewer with filtering and export
* Test email functionality

== Upgrade Notice ==

= 0.1.0 =
Initial release of EnjinMel SMTP plugin with full email delivery and logging features.

== Privacy ==

This plugin stores limited email metadata to aid troubleshooting and deliverability monitoring:

* Stored: recipient email address(es), subject, send status (sent/failed), timestamp, and an error message when available.
* Not stored: email body content or attachments.
* Retention: logs are kept for 90 days by default and purged daily. Developers can adjust the retention via the `enjinmel_smtp_retention_days` filter, clear logs from the admin UI, or purge on uninstall by defining `ENJINMEL_SMTP_PURGE_LOGS_ON_UNINSTALL` in `wp-config.php`.

== Additional Information ==

**Security:**
* API keys are encrypted before storage
* All admin actions use WordPress nonces
* Input sanitization and output escaping throughout
* Capability checks on all admin operations

**Developers:**
* Follows WordPress Coding Standards
* Comprehensive inline documentation
* Extensible architecture with filters and actions

**Support:**
For issues, feature requests, or contributions, please refer to the project repository.
