# Changelog

All notable changes to EnjinMel SMTP will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.5] - 2026-05-26

### Fixed
- Restored legacy EngineMail compatibility helpers for settings, log migration, cron cleanup, and mail failure metadata while keeping current EnjinMel behavior.
- Hardened Send Test Email recipient validation to reject tampered input instead of sanitizing it into a different address.
- Made log-table detection work for both persistent WordPress tables and temporary tables created by the WordPress PHPUnit test suite.

### Changed
- Verified compatibility with WordPress 7.0 and updated `Tested up to` metadata.
- Aligned the WordPress test stack with WordPress 7.0: `wp-phpunit` `^7.0`, PHPUnit `^9.6`, and `yoast/phpunit-polyfills` `^4.0`.
- Renamed PHPUnit test files/classes to PHPUnit-compatible `*_Test.php` / `*_Test` naming.
- Removed the plugin bootstrap from Composer `autoload.files` so Composer dev tools run outside WordPress.

## [0.2.4] - 2026-05-15

### Fixed
- **Security:** Preserve any existing non-null `pre_wp_mail` return value, including `WP_Error`, so earlier mail-blocking/security filters are not bypassed before EnjinMel sends.
- **Security:** Neutralize spreadsheet formula prefixes in exported email log CSV values.
- **Security hardening:** Render dynamic test-email failure messages as text instead of HTML.
- **Development:** Updated locked PHPUnit dev dependency to `12.5.25` to address CVE-2026-24765.

### Changed
- Packaging is now handled via `git archive --worktree-attributes` with `.gitattributes` export-ignore rules to strip tests, specs, docs, and other dev assets from release zips (no more committed `/dist` artifacts).

### Tests
- Added regression coverage for preserving prior `WP_Error` mail blockers.
- Added regression coverage for CSV formula neutralization.

## [0.2.3] - 2025-11-17

### Fixed
- **Compliance:** Removed the manual `load_plugin_textdomain()` call so WordPress automatically loads translations for the plugin slug.
- **Hooks:** Added PHPCS ignores when mirroring `wp_mail_failed`, `wp_mail_succeeded`, and `wp_mail_content_type` while retaining compatibility with core.
- **Uninstall:** Prefixed all uninstall globals before log table cleanup to satisfy NamingConventions checks.

### Changed
- Regenerated `/dist/enjinmel-smtp/` and bundled the new `enjinmel-smtp-0.2.3.zip`, ensuring `languages/` exists for the `/languages` domain path.

## [0.2.2] - 2025-11-07

### Fixed
- **CRITICAL:** Fixed double-encryption bug that could corrupt API keys when settings are saved multiple times
- Added automatic detection and repair of double-encrypted API keys on plugin load
- Added check to prevent re-encryption of already encrypted values (those starting with `v2:` prefix)

### Changed
- Enhanced settings sanitization to be more resilient against encryption issues

## [0.2.1] - 2025-11-07

### Fixed
- **CRITICAL:** Fixed API compatibility with V2 endpoint - removed unsupported fields causing 500 errors
  - Removed `SubmittedContentType` field (not supported in V2 API)
  - Removed `IsHtmlContent` field (not supported in V2 API)
  - Removed `ReplyToEmail` field (not documented in V2 API)
- Made `SenderName` field optional in API payload (only sent when not empty)
- Added default values for empty subject "(no subject)" and empty message " " to prevent API errors

## [0.2.0] - 2025-11-01

### Added
- Password-masked API key field with Show/Hide toggle button
- Composite database index (status, timestamp) for improved query performance
- Multibyte-safe text truncation supporting Unicode, emoji, and international characters
- Email validation at settings save time with immediate user feedback
- TRUNCATE fallback to DELETE for hosts with restricted permissions

### Fixed
- **CRITICAL SECURITY:** Implemented per-message random IV encryption (v2 format) - replaced static IV
- **CRITICAL SECURITY:** SQL injection prevention via table name sanitization and backticks
- **CRITICAL SECURITY:** Fixed asset loading paths causing log viewer UI to break
- **HIGH SECURITY:** Removed duplicate export handler with weak security (CSRF vulnerability)
- **HIGH SECURITY:** Encryption keys no longer autoload (reduced exposure, 99.9% less loading)
- Fixed logging defaulting to OFF on first save - now defaults to enabled
- Fixed email validation logic (was using sanitize_email incorrectly)
- Fixed whitespace-only API key input clearing stored key
- Added backticks to CREATE TABLE for consistency

### Performance
- Added composite index for 50-80% faster filtered log queries
- Encryption keys set to autoload=no - significant memory reduction
- TRUNCATE optimization with DELETE fallback for universal compatibility

### Security
- Per-message random IV (non-deterministic encryption)
- All table names sanitized and backticked in SQL queries
- Export handler now AJAX-only with proper nonce verification
- API key field masked by default (password input type)
- Secrets no longer loaded on every page request

### Changed
- Documentation: cleaned up README test commands and minor grammar fixes
- Enhanced security audit documentation
- Comprehensive implementation documentation added

## [0.1.0] - 2025-10-05

### Added
- Initial release of EnjinMel SMTP plugin
- Enginemailer REST API integration for email delivery
- Encrypted API key storage using AES-256-CBC encryption
- Comprehensive email logging system with database storage
- Admin log viewer with filtering, search, and pagination
- Log export functionality (CSV format)
- Clear all logs functionality with confirmation
- Automatic log retention management (90-day default)
- Test email functionality from settings page
- Admin settings page with API configuration
- Support for custom From Name and From Email
- Force From option to override plugin defaults
- Toggle for enabling/disabling logging
- Daily cron job for log cleanup
- WordPress Coding Standards compliance
- Security features:
  - Nonce verification for all admin actions
  - Input sanitization throughout
  - Output escaping for security
  - Capability checks on admin operations
  - Encrypted API key storage

### Technical Details
- Intercepts `wp_mail()` via `pre_wp_mail` filter
- Custom database table for email logs
- Mirrors WordPress core `wp_mail_succeeded` and `wp_mail_failed` actions
- Supports all standard email features (CC, BCC, Reply-To, attachments)
- Compatible with any plugin using standard `wp_mail()` function

### Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- Enginemailer API key
- Encryption constants in wp-config.php (optional - auto-generated if not provided)

[0.2.5]: https://github.com/liewcf/enjinmel-smtp/releases/tag/v0.2.5
[0.2.4]: https://github.com/liewcf/enjinmel-smtp/releases/tag/v0.2.4
[0.2.3]: https://github.com/liewcf/enjinmel-smtp/releases/tag/v0.2.3
[0.2.2]: https://github.com/liewcf/enjinmel-smtp/releases/tag/v0.2.2
[0.2.1]: https://github.com/liewcf/enjinmel-smtp/releases/tag/v0.2.1
[0.2.0]: https://github.com/liewcf/enjinmel-smtp/releases/tag/v0.2.0
[0.1.0]: https://github.com/liewcf/enjinmel-smtp/releases/tag/v0.1.0
