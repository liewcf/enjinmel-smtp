# EnjinMel SMTP - Publication Checklist

## Plugin Information
- **Version:** 0.1.0
- **WordPress Required:** 5.0+
- **WordPress Tested:** 6.7
- **PHP Required:** 7.4+
- **License:** GPLv2 or later

## ✅ Pre-Publication Checklist (Completed)

### Documentation
- [x] `readme.txt` created for WordPress.org with full documentation
- [x] `CHANGELOG.md` following Keep a Changelog format
- [x] `LICENSE.txt` (GPLv2) included
- [x] Plugin header complete with all metadata
- [x] Installation instructions documented
- [x] FAQ section included

### Code Quality
- [x] Fixed 1823 WordPress Coding Standards violations
- [x] All code compliant with WordPress standards
- [x] 23 intentional warnings (nonce verification in read-only operations, file operations in export)
- [x] Version numbers consistent across all files (0.1.0)

### Security
- [x] All admin actions use WordPress nonces
- [x] Input sanitization with `sanitize_text_field()`, `sanitize_email()`
- [x] Output escaping with `esc_html()`, `esc_attr()`, `esc_url()`
- [x] Capability checks on all admin operations (`manage_options`)
- [x] API keys encrypted with AES-256-CBC before storage
- [x] No sensitive data in repository or distribution

### Functionality
- [x] Email interception via `pre_wp_mail` filter
- [x] Enginemailer REST API integration
- [x] Email logging with database storage
- [x] Admin log viewer with filtering and export
- [x] Test email functionality
- [x] Settings page with configuration
- [x] Automatic log retention (90 days)
- [x] Legacy EngineMail compatibility

### Distribution Package
- [x] Clean distribution zip created: `dist/enjinmel-smtp-0.1.0.zip` (27KB)
- [x] `.distignore` configured to exclude development files
- [x] Package contains only production files:
  - Main plugin file
  - All class files in `/includes`
  - Assets (CSS, JS)
  - readme.txt
  - CHANGELOG.md
  - LICENSE.txt

### Files Excluded from Distribution
- Development files (composer.json, phpunit.xml, etc.)
- Test files and directories
- Vendor directory (dependencies)
- Development scripts
- IDE and OS files
- Documentation files (CLAUDE.md, AGENTS.md, etc.)

## 📦 Distribution Package Contents

```
enjinmel-smtp/
├── enjinmel-smtp.php          # Main plugin file
├── readme.txt                 # WordPress.org readme
├── CHANGELOG.md               # Version history
├── LICENSE.txt                # GPLv2 license
├── assets/
│   ├── css/
│   │   └── log-viewer.css
│   └── js/
│       └── log-viewer.js
└── includes/
    ├── class-enjinmel-smtp-api-client.php
    ├── class-enjinmel-smtp-encryption.php
    ├── class-enjinmel-smtp-log-viewer.php
    ├── class-enjinmel-smtp-logging.php
    └── class-enjinmel-smtp-settings-page.php
```

## 🚀 Next Steps for Publication

### WordPress.org Repository (if applicable)
1. Create WordPress.org developer account
2. Submit plugin to WordPress.org plugin directory
3. Wait for review (typically 2-3 weeks)
4. Respond to any review feedback
5. Plugin goes live after approval

### GitHub Release
1. Create a new release on GitHub: v0.1.0
2. Upload `dist/enjinmel-smtp-0.1.0.zip` as release asset
3. Copy changelog from CHANGELOG.md to release notes
4. Tag the release: `git tag v0.1.0 && git push origin v0.1.0`

### Manual Distribution
1. Use `dist/enjinmel-smtp-0.1.0.zip` for manual installation
2. Users can install via WordPress admin: Plugins → Add New → Upload Plugin
3. Share download link with users

## 📋 Installation Requirements for Users

### Required
- Enginemailer API key from https://portal.enginemailer.com/Account/APIs

### Optional (Enhanced Security)
For shared hosting or high-security environments, users can optionally add these constants to `wp-config.php`:

```php
define('ENJINMEL_SMTP_KEY', 'your-32-character-key-here');
define('ENJINMEL_SMTP_IV', 'your-16-character-iv-here');
```

If not provided, the plugin will auto-generate and store encryption keys in the database.

To generate custom secure keys:
```php
// Generate encryption key (32 characters)
echo bin2hex(random_bytes(16));

// Generate IV (16 characters)
echo bin2hex(random_bytes(8));
```

## 🔍 Post-Publication Testing

After installation in a test environment, verify:
- [ ] Plugin activates without errors
- [ ] Settings page accessible
- [ ] API key can be saved (encrypted)
- [ ] Test email sends successfully
- [ ] Emails are logged correctly
- [ ] Log viewer displays data
- [ ] Export functionality works
- [ ] Cron job scheduled for log cleanup
- [ ] No PHP errors or warnings

## 📝 Support and Maintenance

- **Repository:** https://github.com/liewcf/enjinmel-smtp
- **Issues:** Report bugs via GitHub Issues
- **Updates:** Follow semantic versioning (MAJOR.MINOR.PATCH)

## ✨ Changelog Summary

Version 0.1.0 (2025-10-05):
- Initial release
- Full Enginemailer REST API integration
- Email logging and management
- Admin interface with log viewer
- Security: encrypted storage, input sanitization, output escaping
- Legacy EngineMail compatibility
- WordPress Coding Standards compliant
