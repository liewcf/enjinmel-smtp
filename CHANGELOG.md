# Changelog

All notable changes to EnjinMel SMTP will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2025-10-05

### Added
- Initial release of EnjinMel SMTP plugin
- EnjinMel REST API integration for email delivery
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
- Legacy EngineMail compatibility hooks and naming
- Automatic migration from EngineMail to EnjinMel naming convention
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
- EnjinMel API key
- Encryption constants in wp-config.php

[0.1.0]: https://github.com/liewcf/enjinmel-smtp/releases/tag/v0.1.0
