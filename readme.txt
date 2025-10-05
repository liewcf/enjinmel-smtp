=== EnjinMel SMTP ===
Contributors: cheonfongliew
Tags: email, smtp, transactional email, wp_mail, email delivery
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Replace WordPress default email sending with EnjinMel API for enhanced deliverability and reliability.

== Description ==

EnjinMel SMTP replaces the default WordPress email sending functionality with the powerful EnjinMel REST API (formerly EngineMail) to ensure your transactional emails are delivered reliably and efficiently.

**Key Features:**

* **Seamless Integration** - Automatically intercepts all `wp_mail()` calls
* **Enhanced Deliverability** - Routes emails through EnjinMel's reliable email infrastructure
* **Comprehensive Logging** - Track email sends with detailed logs including timestamps, recipients, and status
* **Test Email Functionality** - Verify your configuration by sending test emails
* **Secure API Key Storage** - API keys are encrypted using AES-256-CBC encryption
* **Log Management** - Automatic log retention with configurable cleanup schedules
* **Admin Interface** - User-friendly settings page with log viewer
* **Backwards Compatible** - Maintains legacy EngineMail hooks for existing integrations

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
3. Add encryption constants to your `wp-config.php` file:
   ```php
   define('ENJINMEL_SMTP_KEY', 'your-32-character-key-here');
   define('ENJINMEL_SMTP_IV', 'your-16-character-iv-here');
   ```
4. Navigate to EnjinMel SMTP settings page
5. Enter your EnjinMel API key (get one at https://portal.enginemailer.com/Account/APIs)
6. Configure your default From Name and From Email
7. Send a test email to verify everything works

== Frequently Asked Questions ==

= Where do I get an API key? =

You can obtain an API key from the EnjinMel portal at https://portal.enginemailer.com/Account/APIs

= What are the encryption constants and why do I need them? =

The plugin uses AES-256-CBC encryption to securely store your API key in the database. The `ENJINMEL_SMTP_KEY` and `ENJINMEL_SMTP_IV` constants must be defined in your `wp-config.php` file. These should be unique random strings.

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
3. Test email functionality

== Changelog ==

= 0.1.0 =
* Initial release
* EnjinMel REST API integration
* Encrypted API key storage
* Email logging with retention management
* Admin log viewer with filtering and export
* Test email functionality
* Legacy EngineMail compatibility hooks
* Automatic migration from EngineMail to EnjinMel naming

== Upgrade Notice ==

= 0.1.0 =
Initial release of EnjinMel SMTP plugin with full email delivery and logging features.

== Additional Information ==

**Security:**
* API keys are encrypted before storage
* All admin actions use WordPress nonces
* Input sanitization and output escaping throughout
* Capability checks on all admin operations

**Developers:**
* Legacy hooks maintained for backwards compatibility
* Follows WordPress Coding Standards
* Comprehensive inline documentation
* Extensible architecture with filters and actions

**Support:**
For issues, feature requests, or contributions, visit: https://github.com/liewcf/enjinmel-smtp
