=== EnjinMel SMTP ===
Contributors: lcf
Tags: email, smtp, transactional email, wp_mail, email delivery
Requires at least: 5.3
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.2.5
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

= 0.2.5 =
* Fixed: Restored legacy EngineMail compatibility helpers for settings, log migration, cron cleanup, and mail failure metadata while keeping current EnjinMel behavior.
* Fixed: Hardened Send Test Email recipient validation to reject tampered input instead of sanitizing it into a different address.
* Fixed: Made log-table detection work for both persistent WordPress tables and temporary tables created by the WordPress PHPUnit test suite.
* Changed: Verified compatibility with WordPress 7.0 and updated `Tested up to`.
* Changed: Aligned the WordPress test stack with WordPress 7.0 using PHPUnit 9.6, `wp-phpunit` 7.0, and PHPUnit Polyfills 4.0.

= 0.2.4 =
* Fixed: Security - Preserve prior non-null `pre_wp_mail` return values, including `WP_Error`, before sending through EnjinMel.
* Fixed: Security - Neutralize spreadsheet formula prefixes in exported email log CSV values.
* Hardened: Insert dynamic Send Test Email failure messages as text instead of HTML.
* Changed: Updated PHPUnit dev dependency to `12.5.25` to address CVE-2026-24765.
* Tests: Added regression coverage for prior `WP_Error` preservation and CSV formula neutralization.

= 0.2.3 =
- Fix: Removes manual `load_plugin_textdomain()` so WordPress automatically loads translations for the plugin slug.
- Fix: Flags `wp_mail_failed`, `wp_mail_succeeded`, and `wp_mail_content_type` hooks with PHPCS ignores while mirroring core behavior.
- Fix: Prefixed uninstall globals before dropping log tables to satisfy NamingConventions checks.
- Changed: Rebuilt `/dist/enjinmel-smtp` and generated `enjinmel-smtp-0.2.3.zip`, including the required `/languages` folder referenced by the Domain Path.

= 0.2.2 =
* Fixed: CRITICAL - Double-encryption bug preventing API keys from working
* Fixed: Automatic detection and repair of corrupted double-encrypted keys
* Fixed: Added safeguard to prevent re-encryption of already encrypted values

= 0.2.1 =
* Fixed: CRITICAL - API V2 compatibility (removed unsupported fields causing 500 errors)
* Fixed: Removed SubmittedContentType, IsHtmlContent, and ReplyToEmail fields not in V2 API
* Fixed: Made SenderName optional (only sent when not empty)
* Fixed: Added default values for empty subject and message fields

= 0.2.0 =
* **CRITICAL SECURITY:** Fixed per-message random IV encryption (replaced static IV)
* **CRITICAL SECURITY:** Fixed SQL injection prevention via table sanitization
* **CRITICAL SECURITY:** Fixed asset loading paths causing log viewer UI to break
* **HIGH SECURITY:** Removed duplicate export handler (CSRF vulnerability)
* **HIGH SECURITY:** Encryption keys no longer autoload (99.9% less loading)
* Added: Password-masked API key field with Show/Hide toggle
* Added: Composite index for 50-80% faster filtered queries
* Added: Multibyte-safe text truncation (Unicode, emoji support)
* Added: Email validation at save time with user feedback
* Added: TRUNCATE fallback for universal host compatibility
* Fixed: Logging now defaults to enabled on first save
* Fixed: Email validation logic corrected
* Fixed: Whitespace API key handling
* Performance: Significant memory reduction and query optimization

= 0.1.0 =
* Initial release
* Enginemailer REST API integration
* Encrypted API key storage
* Email logging with retention management
* Admin log viewer with filtering and export
* Test email functionality

== Upgrade Notice ==

= 0.2.5 =
COMPATIBILITY UPDATE: Verifies WordPress 7.0 support, restores legacy EngineMail upgrade paths, and hardens Send Test Email validation.

= 0.2.4 =
RECOMMENDED SECURITY UPDATE: Preserves prior mail-blocking filters before EnjinMel sends, neutralizes spreadsheet formula prefixes in CSV log exports, hardens Send Test Email error rendering, and updates PHPUnit dev tooling.

= 0.2.3 =
RECOMMENDED UPDATE: Addresses WordPress.org plugin check findings by removing the discouraged `load_plugin_textdomain()` call, properly prefixing uninstall globals, and regenerating the distributable bundle (new `enjinmel-smtp-0.2.3.zip`) including the `/languages` directory referenced by the Domain Path.

= 0.2.2 =
CRITICAL UPDATE: Fixes double-encryption bug that prevented API keys from working. Includes automatic repair for affected installations. Update immediately if experiencing email delivery issues.

= 0.2.1 =
CRITICAL UPDATE: Fixes API V2 compatibility issues causing 500 errors. Updates immediately if you're unable to send emails after upgrading to 0.2.0.

= 0.2.0 =
CRITICAL SECURITY UPDATE: Fixes multiple security vulnerabilities including encryption weaknesses and SQL injection prevention. Adds performance improvements and new security features. Update immediately!

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
* Existing `pre_wp_mail` blockers are preserved before EnjinMel sends
* CSV log exports neutralize spreadsheet formula prefixes

**Developers:**
* Follows WordPress Coding Standards
* Comprehensive inline documentation
* Extensible architecture with filters and actions

**Support:**
For issues, feature requests, or contributions, please refer to the project repository.
