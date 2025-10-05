# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin that replaces the default WordPress email sending functionality with the EnjinMel REST API for enhanced deliverability and reliability. The plugin intercepts `wp_mail()` calls and sends emails through the EnjinMel API instead of traditional SMTP.

## Architecture

### Core Components

- **Main Plugin File** (`enjinmel-smtp.php`): Bootstraps the plugin, registers admin menus, and wires settings
- **API Client** (`includes/class-enjinmel-smtp-api-client.php`): Handles communication with the EnjinMel REST API
- **Encryption** (`includes/class-enjinmel-smtp-encryption.php`): Securely encrypts API keys using OpenSSL
- **Logging** (`includes/class-enjinmel-smtp-logging.php`): Logs email sends to custom database table with retention management
- **Settings Page** (`includes/class-enjinmel-smtp-settings-page.php`): Admin interface for configuration

### Key Integration Points

- Uses `pre_wp_mail` filter to intercept email sends
- Creates custom database table `{$wpdb->prefix}enjinmel_smtp_logs` for email logging
- Implements daily cron job for log retention (default: 90 days) using `enjinmel_smtp_retention_daily`
- Requires `ENJINMEL_SMTP_KEY` and `ENJINMEL_SMTP_IV` constants in wp-config.php for encryption

## Development Commands

### Testing
```bash
# Run PHPUnit tests
composer test
# Or directly with npx
npx wp-env run phpunit
```

### Code Quality
```bash
# Check syntax of individual PHP files
php -l includes/class-enjinmel-smtp-encryption.php
# Run PHPCS (WordPress Coding Standards)
./vendor/bin/phpcs
```

### Feature Development
```bash
# Create new feature branch and spec
bash scripts/create-new-feature.sh "describe feature"
# Get active feature paths
bash scripts/get-feature-paths.sh
```

### Local Development
```bash
# Start WordPress environment
npx wp-env start
```

## Development Workflow

### Feature Branching
- Branch names follow `NNN-short-summary` convention (e.g., `001-wordpress-enjinmel-smtp`)
- Use `scripts/create-new-feature.sh` to scaffold new features
- Specs and planning docs live in `specs/NNN-name/` directories

### Testing Guidelines
- Unit tests in `tests/unit/` should mirror behavior under test
- Integration tests in `tests/integration/` for API interactions
- Tests use `WP_UnitTestCase` and WordPress testing framework
- Define test encryption constants in test files:
  ```php
  define('ENJINMEL_SMTP_KEY', 'unit-test-key');
  define('ENJINMEL_SMTP_IV', 'unit-test-iv');
  // Legacy constants are still recognised where necessary.
  ```

## Security & Configuration

### Encryption Requirements
- API keys are encrypted before storage in WordPress options
- Requires `ENJINMEL_SMTP_KEY` and `ENJINMEL_SMTP_IV` constants in wp-config.php
- Uses AES-256-CBC encryption with keys derived from SHA-256 hashes

### Security Practices
- Escape all output using WordPress functions (`esc_html`, `esc_attr`)
- Sanitize all input before processing
- Use WordPress nonces for admin form submissions
- Never hardcode secrets - use constants or environment variables

## Code Style

- Follow WordPress Coding Standards
- Use 4-space indentation, UTF-8 encoding, LF line endings
- Function naming: `enjinmel_smtp_{verb_noun}`
- Class naming: `EngineMail_SMTP_{Name}`
- PHP files use `class-*.php` naming pattern
- Target PHP 7.4+ compatibility

## Database Schema

The plugin creates a custom table on activation:
```sql
CREATE TABLE {$wpdb->prefix}enjinmel_smtp_logs (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    timestamp DATETIME NOT NULL,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL,
    error_message TEXT,
    PRIMARY KEY (id),
    KEY timestamp (timestamp)
);
```

## API Integration

- Endpoint: `https://api.enginemailer.com/RESTAPI/V2/Submission/SendEmail`
- Uses JSON payload with API key in headers
- Supports attachments up to 5MB
- Handles HTML and plain text content types
- Supports CC, BCC, and Reply-To headers

## Common Development Tasks

### Adding New Settings
1. Add field to settings page in `class-enjinmel-smtp-settings-page.php`
2. Update sanitization in `enjinmel_smtp_settings_sanitize()` function
3. Add validation and default handling

### Extending API Client
1. Modify payload building in `build_payload()` method
2. Add new filters/hooks as needed
3. Update tests in `tests/unit/test-api-client.php`

### Adding Logging Features
1. Extend `EngineMail_SMTP_Logging` class
2. Add new hooks to `wp_mail_succeeded` and `wp_mail_failed` actions
3. Update database schema if needed (requires migration)
