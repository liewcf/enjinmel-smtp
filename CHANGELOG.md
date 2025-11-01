# Changelog

All notable changes to EnjinMel SMTP will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[0.2.0]: https://github.com/liewcf/enjinmel-smtp/releases/tag/v0.2.0
[0.1.0]: https://github.com/liewcf/enjinmel-smtp/releases/tag/v0.1.0
