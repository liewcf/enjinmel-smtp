# EnjinMel SMTP

[![WordPress Plugin Version](https://img.shields.io/badge/version-0.1.0-blue.svg)](https://github.com/liewcf/enjinmel-smtp)
[![WordPress Compatibility](https://img.shields.io/badge/wordpress-5.3%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/php-7.4%2B-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPLv2%2B-green.svg)](LICENSE.txt)

Replace WordPress default email sending with the Enginemailer REST API for enhanced deliverability and reliability.

> **Note:** EnjinMel SMTP is an independent project built to integrate WordPress with the Enginemailer email service. This plugin maintains backward compatibility with legacy EngineMail hooks.

## Features

- **Seamless Integration** - Automatically intercepts all `wp_mail()` calls
- **Enhanced Deliverability** - Routes emails through Enginemailer's reliable infrastructure
- **Comprehensive Logging** - Track all email sends with timestamps, recipients, and status
- **Test Email Functionality** - Verify configuration by sending test emails
- **Secure Storage** - API keys encrypted using AES-256-CBC encryption
- **Log Management** - Automatic log retention with configurable cleanup schedules
- **Admin Interface** - User-friendly settings page with advanced log viewer
- **Export Capability** - Export logs to CSV for analysis
- **Legacy Compatible** - Maintains EngineMail hooks for existing integrations
- **Security First** - Nonce verification, input sanitization, output escaping throughout

## Requirements

- **WordPress:** 5.3 or higher
- **PHP:** 7.4 or higher
- **Enginemailer API Key:** Available at [https://portal.enginemailer.com/Account/APIs](https://portal.enginemailer.com/Account/APIs)

## Installation

### Via WordPress Admin

1. Download the latest release from [GitHub Releases](https://github.com/liewcf/enjinmel-smtp/releases)
2. Navigate to **Plugins → Add New → Upload Plugin**
3. Choose the downloaded ZIP file and click **Install Now**
4. Click **Activate Plugin**
5. Go to **EnjinMel SMTP → Settings** to configure your API key

### Manual Installation

1. Upload the `enjinmel-smtp` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Configure the plugin via **EnjinMel SMTP → Settings**

### Via Composer

```bash
composer require enjinmel/enjinmel-smtp
```

## Configuration

### Basic Setup

1. Navigate to **EnjinMel SMTP → Settings**
2. Enter your **Enginemailer API Key**
3. Set your default **From Name** and **From Email**
4. Click **Save Changes**
5. Use the **Send Test Email** feature to verify everything works

### Advanced Configuration (Optional)

For enhanced security in shared hosting or high-security environments, add these constants to your `wp-config.php`:

```php
// Custom encryption keys (recommended for production)
define('ENJINMEL_SMTP_KEY', 'your-32-character-encryption-key');
define('ENJINMEL_SMTP_IV', 'your-16-character-iv');
```

Generate secure keys using:
```php
// Generate encryption key
echo bin2hex(random_bytes(16)); // 32 characters

// Generate IV
echo bin2hex(random_bytes(8));  // 16 characters
```

If these constants are not defined, the plugin will auto-generate and store encryption keys in the database.

### Settings Options

- **API Key** - Your Enginemailer API key (encrypted before storage)
- **Sender Name** - Default sender name for outgoing emails
- **Sender Email** - Default sender email address
- **Template ID** - Optional Enginemailer template ID
- **Campaign Name** - Optional campaign identifier for tracking
- **Force Sender** - Override sender details set by other plugins
- **Enable Logging** - Toggle email logging (enabled by default)

## Usage

Once configured, the plugin automatically handles all WordPress emails. No code changes needed!

### Programmatic Email Sending

```php
// Standard wp_mail() - automatically uses EnjinMel SMTP
wp_mail(
    'recipient@example.com',
    'Subject Line',
    'Email message content',
    ['Content-Type: text/html; charset=UTF-8']
);

// With attachments
wp_mail(
    'recipient@example.com',
    'Subject with Attachment',
    'Message content',
    [],
    ['/path/to/attachment.pdf']
);
```

### Hooks & Filters

The plugin provides numerous hooks for customization:

```php
// Modify API payload before sending
add_filter('enjinmel_smtp_payload', function($payload, $normalized, $settings) {
    $payload['CustomField'] = 'value';
    return $payload;
}, 10, 3);

// Modify request timeout (default: 15 seconds)
add_filter('enjinmel_smtp_request_timeout', function($timeout) {
    return 30;
});

// Modify log retention (default: 90 days)
add_filter('enjinmel_smtp_retention_days', function($days) {
    return 30;
});

// Before email send
add_action('enjinmel_smtp_before_send', function($normalized, $payload) {
    // Custom logic before API call
}, 10, 2);

// After email send
add_action('enjinmel_smtp_after_send', function($normalized, $payload, $result) {
    // Custom logic after API call
}, 10, 3);
```

## Log Viewer

Access comprehensive email logs at **EnjinMel SMTP → Email Logs**:

- **Filter by status** (sent/failed)
- **Search** by recipient or subject
- **Date range filtering**
- **Export to CSV**
- **Bulk delete operations**
- **Pagination** with configurable items per page

## Development

### Local Development Setup

```bash
# Clone the repository
git clone https://github.com/liewcf/enjinmel-smtp.git
cd enjinmel-smtp

# Install dependencies
composer install

# Start WordPress environment (requires @wordpress/env)
npx wp-env start
```

### Running Tests

```bash
# Run all tests
composer test

# Run PHPUnit tests directly
npx wp-env run phpunit

# Check code standards
./vendor/bin/phpcs

# Fix code standards automatically
./vendor/bin/phpcbf
```

### Development Commands

```bash
# Create new feature branch with spec
bash scripts/create-new-feature.sh "feature description"

# Get active feature paths
bash scripts/get-feature-paths.sh

# Build distribution package
bash scripts/build-dist.sh
```

### Code Standards

- Follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- PHP 7.4+ compatibility
- PSR-4 autoloading (via Composer)
- Comprehensive PHPDoc comments
- Security-first approach

## Security

### Security Features

- **Encrypted Storage** - API keys encrypted with AES-256-CBC
- **Nonce Protection** - All admin actions verified with WordPress nonces
- **Capability Checks** - `manage_options` capability required for all admin functions
- **Input Sanitization** - All user inputs sanitized before processing
- **Output Escaping** - All outputs escaped to prevent XSS
- **Prepared Statements** - All database queries use `$wpdb->prepare()`

### Reporting Security Issues

If you discover a security vulnerability, please email security@example.com. Do not create a public issue.

## Compatibility

### WordPress Plugins

Works seamlessly with any plugin using `wp_mail()`:

- ✅ WooCommerce
- ✅ Contact Form 7
- ✅ Gravity Forms
- ✅ Easy Digital Downloads
- ✅ MemberPress
- ✅ BuddyPress
- ✅ bbPress

### Legacy EngineMail

Maintains backward compatibility with legacy EngineMail hooks:

- `enginemail_smtp_payload`
- `enginemail_smtp_before_send`
- `enginemail_smtp_after_send`
- `enginemail_smtp_request_timeout`
- `enginemail_smtp_retention_days`

## Uninstallation

Upon deactivation:
- Cron jobs are automatically cleared
- Settings remain in database

Upon deletion:
- Plugin settings are removed
- Encryption keys are deleted
- Scheduled events are cleared
- **Logs are preserved by default**

To delete logs on uninstall, add to `wp-config.php`:
```php
define('ENJINMEL_SMTP_PURGE_LOGS_ON_UNINSTALL', true);
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

### Current Version: 0.1.0

- Initial release with full Enginemailer REST API integration
- Email logging and management
- Admin log viewer with filtering and export
- Security: encrypted storage, sanitization, escaping
- Legacy EngineMail compatibility

## Support

- **Documentation:** [GitHub Wiki](https://github.com/liewcf/enjinmel-smtp/wiki)
- **Issues:** [GitHub Issues](https://github.com/liewcf/enjinmel-smtp/issues)
- **Discussions:** [GitHub Discussions](https://github.com/liewcf/enjinmel-smtp/discussions)

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

### Development Workflow

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes
4. Run tests: `composer test`
5. Check code standards: `./vendor/bin/phpcs`
6. Commit your changes: `git commit -m 'Add amazing feature'`
7. Push to the branch: `git push origin feature/amazing-feature`
8. Open a Pull Request

## License

This project is licensed under the GNU General Public License v2.0 or later - see the [LICENSE.txt](LICENSE.txt) file for details.

## Credits

- **Author:** [Liew CheonFong](https://github.com/liewcf)
- **Contributors:** [List of contributors](https://github.com/liewcf/enjinmel-smtp/graphs/contributors)

## Acknowledgments

- Built for the WordPress community
- Integrates with Enginemailer email service
- Maintains backward compatibility with legacy EngineMail implementations

---

**Made with ❤️ for WordPress**
