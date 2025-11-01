# EnjinMel SMTP - Implementation Plan

**Created:** November 2025  
**Based on:** SECURITY-AUDIT.md  
**Current Version:** 0.1.0  
**Target Versions:** v0.1.1 (patch), v0.2.0 (minor)

---

## 🎯 Overview

This plan addresses 21 identified issues across 4 priority levels. We'll implement fixes in 3 phases:

1. **Phase 1 (Immediate):** Critical security and blocking issues - Required before any production use
2. **Phase 2 (v0.1.1 Patch):** High-priority functional bugs - 1-2 week release
3. **Phase 3 (v0.2.0 Minor):** Medium-priority improvements - 1-2 month release

---

## 📅 PHASE 1: IMMEDIATE FIXES (Pre-Production)

**Timeline:** 1-3 days  
**Blockers:** Cannot release to production without these fixes

### Issue #1: Fix Asset Loading (CRITICAL - Blocking)

**File:** `includes/class-enjinmel-smtp-log-viewer.php`

**Tasks:**
- [ ] Update line 53: Change asset path from `plugins_url( 'assets/css/log-viewer.css', __DIR__ )` to `plugins_url( '../assets/css/log-viewer.css', __FILE__ )`
- [ ] Update line 60: Change asset path from `plugins_url( 'assets/js/log-viewer.js', __DIR__ )` to `plugins_url( '../assets/js/log-viewer.js', __FILE__ )`
- [ ] Test log viewer page loads CSS/JS correctly
- [ ] Verify all styles render properly
- [ ] Verify all JavaScript functions work (bulk delete, export, etc.)

**Verification:**
```bash
# Navigate to log viewer in browser
# Check browser console for 404 errors (should be none)
# Verify CSS styling appears correctly
# Test bulk actions and export functionality
```

---

### Issue #2: Implement Per-Message IV Encryption (CRITICAL - Security)

**File:** `includes/class-enjinmel-smtp-encryption.php`

**Tasks:**
- [ ] Backup current encryption implementation
- [ ] Update `encrypt()` method to generate random IV per call
- [ ] Store IV with ciphertext using `v2:` prefix format
- [ ] Update `decrypt()` method to detect versioned format
- [ ] Implement backward compatibility for legacy (non-versioned) values
- [ ] Add comments explaining versioning scheme
- [ ] Test new encryption/decryption
- [ ] Test legacy decryption still works
- [ ] Optional: Add lazy re-encryption on successful legacy decrypt

**Implementation Steps:**

1. **Update encrypt() method:**
```php
public static function encrypt( $data ) {
    if ( '' === $data ) {
        return '';
    }

    $creds = self::get_credentials();
    if ( is_wp_error( $creds ) ) {
        return $creds;
    }

    list( $key ) = $creds; // Only need key now

    $iv_len = openssl_cipher_iv_length( self::ENCRYPTION_METHOD );
    $iv     = random_bytes( $iv_len );
    
    $raw = openssl_encrypt( $data, self::ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv );
    if ( false === $raw ) {
        return new WP_Error( 'enjinmel_encryption_failed', __( 'Unable to encrypt value.', 'enjinmel-smtp' ) );
    }
    
    // Version 2: Random IV stored with ciphertext
    return 'v2:' . base64_encode( $iv . $raw );
}
```

2. **Update decrypt() method:**
```php
public static function decrypt( $data ) {
    if ( '' === $data ) {
        return '';
    }

    $creds = self::get_credentials();
    if ( is_wp_error( $creds ) ) {
        return $creds;
    }

    list( $key, $legacy_iv ) = $creds;
    
    // Version 2: Random IV embedded in ciphertext
    if ( strncmp( $data, 'v2:', 3 ) === 0 ) {
        $blob = base64_decode( substr( $data, 3 ), true );
        if ( false === $blob ) {
            return new WP_Error( 'enjinmel_decryption_failed', __( 'Unable to decrypt value.', 'enjinmel-smtp' ) );
        }
        
        $iv_len = openssl_cipher_iv_length( self::ENCRYPTION_METHOD );
        $iv     = substr( $blob, 0, $iv_len );
        $raw    = substr( $blob, $iv_len );
        
        $plain = openssl_decrypt( $raw, self::ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv );
        if ( false === $plain ) {
            return new WP_Error( 'enjinmel_decryption_failed', __( 'Unable to decrypt value.', 'enjinmel-smtp' ) );
        }
        
        return $plain;
    }
    
    // Legacy format: Static IV from credentials
    $plain = openssl_decrypt( $data, self::ENCRYPTION_METHOD, $key, 0, $legacy_iv );
    if ( false === $plain ) {
        return new WP_Error( 'enjinmel_decryption_failed', __( 'Unable to decrypt value.', 'enjinmel-smtp' ) );
    }
    
    return $plain;
}
```

**Verification:**
```bash
# Test new encryption
# 1. Save new API key in settings
# 2. Verify it's stored with v2: prefix in database
# 3. Test email sending works
# 4. Test decryption of legacy keys (if any exist)
```

---

### Issue #3: Sanitize and Backtick Table Names (CRITICAL - Security)

**File:** `includes/class-enjinmel-smtp-log-viewer.php`

**Tasks:**
- [ ] Add sanitization and backticks to line 374 (COUNT query)
- [ ] Add sanitization and backticks to line 382 (SELECT query)
- [ ] Add sanitization and backticks to line 415 (DELETE query)
- [ ] Add sanitization and backticks to line 476 (export SELECT query)
- [ ] Add sanitization and backticks to line 514 (TRUNCATE query)
- [ ] Review all other raw SQL in project for similar issues
- [ ] Test all queries still work correctly

**Implementation:**

Replace all instances of:
```php
$table = enjinmel_smtp_active_log_table();
```

With:
```php
$table = enjinmel_smtp_sanitize_table_name( enjinmel_smtp_active_log_table() );
```

And wrap table names in backticks in all SQL:
```php
// Line 374
$count_sql = "SELECT COUNT(*) FROM `{$table}` WHERE {$where_sql}";

// Line 382
$logs_sql = "SELECT * FROM `{$table}` WHERE {$where_sql} ORDER BY timestamp DESC LIMIT %d OFFSET %d";

// Line 415
$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE id IN ({$placeholders})", $log_ids ) );

// Line 476
$logs_sql = "SELECT * FROM `{$table}` WHERE {$where_sql} ORDER BY timestamp DESC";

// Line 514
$deleted = $wpdb->query( "TRUNCATE TABLE `{$table}`" );
```

**Also check:**
- [ ] `includes/class-enjinmel-smtp-logging.php` for any raw SQL
- [ ] `enjinmel-smtp.php` activation/deactivation hooks

**Verification:**
```bash
# Test all log viewer functions:
# - View logs
# - Filter logs
# - Delete selected logs
# - Export logs
# - Clear all logs
```

---

### Issue #4: Add Nonce/Capability to Export Handler (HIGH - Security)

**File:** `enjinmel-smtp.php`

**Option A: Secure the admin_init handler**

**Tasks:**
- [ ] Add capability check to `enjinmel_smtp_handle_export_logs()`
- [ ] Add nonce verification
- [ ] Update export links to include nonce parameter
- [ ] Test export functionality

**Implementation:**
```php
function enjinmel_smtp_handle_export_logs() {
    if ( ! isset( $_GET['action'], $_GET['page'], $_GET['nonce'] ) ) {
        return;
    }
    
    if ( 'enjinmel_smtp_export_logs' !== $_GET['action'] || 'enjinmel-smtp-logs' !== $_GET['page'] ) {
        return;
    }
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to export logs.', 'enjinmel-smtp' ) );
    }
    
    if ( ! wp_verify_nonce( $_GET['nonce'], 'enjinmel_smtp_log_viewer' ) ) {
        wp_die( esc_html__( 'Invalid security token.', 'enjinmel-smtp' ) );
    }
    
    EnjinMel_SMTP_Log_Viewer::ajax_export_logs();
}
```

**Option B: Remove admin_init handler (RECOMMENDED)**

**Tasks:**
- [ ] Remove lines 147-160 from `enjinmel-smtp.php`
- [ ] Keep AJAX-only export via `wp_ajax_enjinmel_smtp_export_logs`
- [ ] Update documentation if needed
- [ ] Test export still works via AJAX

**Verification:**
```bash
# Test CSV export from log viewer
# Verify nonce is checked
# Test as non-admin user (should fail)
```

---

## 📦 PHASE 2: v0.1.1 PATCH RELEASE

**Timeline:** 1-2 weeks after Phase 1  
**Goal:** Fix high-priority functional bugs

### Issue #5: Fix Logging Default Behavior

**File:** `enjinmel-smtp.php`

**Tasks:**
- [ ] Update `enjinmel_smtp_settings_sanitize()` at line 305
- [ ] Preserve existing logging value or default to enabled
- [ ] Test first-time save enables logging by default
- [ ] Test subsequent saves preserve checkbox state
- [ ] Update tests if needed

**Implementation:**
```php
// Line 305 - Replace with:
if ( isset( $input['enable_logging'] ) ) {
    $output['enable_logging'] = ! empty( $input['enable_logging'] ) ? 1 : 0;
} else {
    // Default to enabled (1) if never set, otherwise preserve existing
    $output['enable_logging'] = isset( $existing['enable_logging'] ) ? $existing['enable_logging'] : 1;
}
```

**Verification:**
```bash
# Fresh install test:
# 1. Install plugin
# 2. Save settings without touching logging checkbox
# 3. Verify enable_logging = 1 in database
# 4. Send test email
# 5. Verify log entry created

# Existing install test:
# 1. Uncheck logging
# 2. Save settings
# 3. Verify enable_logging = 0
# 4. Re-check logging
# 5. Save settings
# 6. Verify enable_logging = 1
```

---

### Issue #6: Set Encryption Keys autoload=no

**File:** `includes/class-enjinmel-smtp-encryption.php`

**Tasks:**
- [ ] Update `get_or_create_stored_keys()` method at line 104-118
- [ ] Change from `update_option()` to `add_option()` with autoload=no
- [ ] Add migration for existing installs
- [ ] Test new installations
- [ ] Test existing installations upgrade correctly

**Implementation:**
```php
private static function get_or_create_stored_keys() {
    $key = get_option( 'enjinmel_smtp_encryption_key', null );
    $iv  = get_option( 'enjinmel_smtp_encryption_iv', null );

    if ( null === $key || null === $iv ) {
        $key = self::generate_random_key();
        $iv  = self::generate_random_key();

        // Use add_option with autoload=no for security
        $key_added = add_option( 'enjinmel_smtp_encryption_key', $key, '', 'no' );
        $iv_added  = add_option( 'enjinmel_smtp_encryption_iv', $iv, '', 'no' );

        if ( ! $key_added || ! $iv_added ) {
            return new WP_Error( 'enjinmel_key_generation_failed', __( 'Failed to generate and store encryption keys.', 'enjinmel-smtp' ) );
        }
    } else {
        // Migration: Update existing options to autoload=no
        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->options} SET autoload = 'no' WHERE option_name IN (%s, %s)",
            'enjinmel_smtp_encryption_key',
            'enjinmel_smtp_encryption_iv'
        ) );
    }

    return array( $key, $iv );
}
```

**Verification:**
```bash
# Check autoload status in database:
SELECT option_name, autoload FROM wp_options WHERE option_name LIKE 'enjinmel_smtp_encryption%';
# Should show autoload = 'no'

# Verify encryption still works after change
```

---

### Issue #7: Add TRUNCATE Fallback

**File:** `includes/class-enjinmel-smtp-log-viewer.php`

**Tasks:**
- [ ] Update `ajax_clear_all_logs()` method at line 504-521
- [ ] Add fallback to DELETE FROM if TRUNCATE fails
- [ ] Test on hosts with TRUNCATE permission
- [ ] Test fallback behavior
- [ ] Add user feedback message

**Implementation:**
```php
public static function ajax_clear_all_logs() {
    check_ajax_referer( 'enjinmel_smtp_log_viewer', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'enjinmel-smtp' ) ), 403 );
    }

    global $wpdb;
    $table = enjinmel_smtp_sanitize_table_name( enjinmel_smtp_active_log_table() );

    // Try TRUNCATE first (faster)
    $deleted = $wpdb->query( "TRUNCATE TABLE `{$table}`" );
    
    // Fallback to DELETE if TRUNCATE fails
    if ( false === $deleted ) {
        $deleted = $wpdb->query( "DELETE FROM `{$table}`" );
    }

    if ( false === $deleted ) {
        wp_send_json_error( array( 'message' => __( 'Failed to clear all logs.', 'enjinmel-smtp' ) ), 500 );
    }

    wp_send_json_success( array( 'message' => __( 'All logs cleared successfully.', 'enjinmel-smtp' ) ) );
}
```

**Verification:**
```bash
# Test on different hosting environments
# Verify logs are cleared successfully
# Check for any error messages
```

---

### Issue #8: Validate Sender Email at Save Time

**File:** `enjinmel-smtp.php`

**Tasks:**
- [ ] Update `enjinmel_smtp_settings_sanitize()` at line 303
- [ ] Add validation using `is_email()`
- [ ] Add settings error if invalid
- [ ] Preserve existing valid email on validation failure
- [ ] Test with valid emails
- [ ] Test with invalid emails
- [ ] Verify user sees error message

**Implementation:**
```php
// Replace line 303 with:
$from_email = isset( $input['from_email'] ) ? sanitize_email( $input['from_email'] ) : '';
if ( ! empty( $from_email ) && ! is_email( $from_email ) ) {
    add_settings_error(
        enjinmel_smtp_option_key(),
        'enjinmel_invalid_from_email',
        __( 'The sender email address is invalid. Please enter a valid email address.', 'enjinmel-smtp' ),
        'error'
    );
    // Preserve existing valid email
    $from_email = isset( $existing['from_email'] ) ? $existing['from_email'] : '';
}
$output['from_email'] = $from_email;
```

**Verification:**
```bash
# Test cases:
# 1. Save with valid email (test@example.com) - should succeed
# 2. Save with invalid email (not-an-email) - should show error and preserve old value
# 3. Save with empty email - should succeed (optional field)
# 4. Save with email containing spaces - should sanitize and validate
```

---

## 🚀 PHASE 3: v0.2.0 MINOR RELEASE

**Timeline:** 1-2 months  
**Goal:** Performance improvements and quality enhancements

### Issue #11: Add Composite Index

**File:** `enjinmel-smtp.php` (activation hook)

**Tasks:**
- [ ] Update table schema in `enjinmel_smtp_activate()` at line 412
- [ ] Add `KEY status_ts (status, timestamp)` to CREATE TABLE statement
- [ ] Create upgrade routine for existing installations
- [ ] Test on fresh install
- [ ] Test on existing install with data
- [ ] Verify query performance improvement

**Implementation:**
```php
function enjinmel_smtp_activate() {
    global $wpdb;

    $table_name      = enjinmel_smtp_log_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    include_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        timestamp DATETIME NOT NULL,
        to_email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        status VARCHAR(20) NOT NULL,
        error_message TEXT,
        PRIMARY KEY  (id),
        KEY timestamp (timestamp),
        KEY status_ts (status, timestamp)
    ) {$charset_collate};";

    dbDelta( $sql );

    if ( class_exists( 'EnjinMel_SMTP_Logging' ) ) {
        EnjinMel_SMTP_Logging::schedule_events();
    }
}
```

**Verification:**
```sql
-- Check indexes exist
SHOW INDEX FROM wp_enjinmel_smtp_logs;

-- Test query performance
EXPLAIN SELECT * FROM wp_enjinmel_smtp_logs 
WHERE status = 'sent' 
ORDER BY timestamp DESC 
LIMIT 20;
-- Should use status_ts index
```

---

### Issue #12: Implement Proper Uninstall

**File:** `uninstall.php`

**Tasks:**
- [ ] Read current `uninstall.php` contents
- [ ] Add deletion of plugin settings
- [ ] Add deletion of encryption keys
- [ ] Add conditional table drop (respecting PURGE constant)
- [ ] Add deletion of scheduled cron events
- [ ] Test uninstall process
- [ ] Verify cleanup is complete

**Implementation:**
```php
<?php
/**
 * Uninstall script for EnjinMel SMTP
 *
 * @package EnjinMel_SMTP
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

global $wpdb;

// Delete plugin settings
delete_option( 'enjinmel_smtp_settings' );
delete_option( 'enjinmel_smtp_encryption_key' );
delete_option( 'enjinmel_smtp_encryption_iv' );

// Clear scheduled events
wp_clear_scheduled_hook( 'enjinmel_smtp_retention_daily' );

// Drop custom table if configured to purge on uninstall
if ( defined( 'ENJINMEL_SMTP_PURGE_LOGS_ON_UNINSTALL' ) && ENJINMEL_SMTP_PURGE_LOGS_ON_UNINSTALL ) {
    $table_name = $wpdb->prefix . 'enjinmel_smtp_logs';
    $wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}
```

**Verification:**
```bash
# Before uninstall, check database:
SELECT * FROM wp_options WHERE option_name LIKE 'enjinmel%';
SHOW TABLES LIKE '%enjinmel%';

# Uninstall plugin

# After uninstall, verify cleanup:
SELECT * FROM wp_options WHERE option_name LIKE 'enjinmel%';
# Should return no results (unless PURGE_LOGS constant not set)

# Check table still exists if constant not set
SHOW TABLES LIKE '%enjinmel%';
```

---

### Issue #9: Use type="password" for API Key

**File:** `includes/class-enjinmel-smtp-settings-page.php`

**Tasks:**
- [ ] Locate API key input field
- [ ] Change input type to "password"
- [ ] Add "Show/Hide" toggle button
- [ ] Add JavaScript for toggle functionality
- [ ] Style toggle button
- [ ] Test show/hide functionality
- [ ] Test masked value preservation

**Implementation:**
```php
// In settings field rendering:
<tr>
    <th scope="row">
        <label for="enjinmel_smtp_api_key"><?php echo esc_html__( 'API Key', 'enjinmel-smtp' ); ?></label>
    </th>
    <td>
        <div class="api-key-wrapper">
            <input type="password" 
                   id="enjinmel_smtp_api_key" 
                   name="enjinmel_smtp_settings[api_key]" 
                   value="<?php echo esc_attr( $masked_key ); ?>" 
                   class="regular-text" />
            <button type="button" 
                    id="enjinmel_smtp_toggle_api_key" 
                    class="button button-secondary">
                <?php echo esc_html__( 'Show', 'enjinmel-smtp' ); ?>
            </button>
        </div>
        <p class="description">
            <?php echo esc_html__( 'Your Enginemailer API key', 'enjinmel-smtp' ); ?>
        </p>
    </td>
</tr>

<script>
(function($) {
    $('#enjinmel_smtp_toggle_api_key').on('click', function() {
        var input = $('#enjinmel_smtp_api_key');
        var button = $(this);
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            button.text('<?php echo esc_js( __( 'Hide', 'enjinmel-smtp' ) ); ?>');
        } else {
            input.attr('type', 'password');
            button.text('<?php echo esc_js( __( 'Show', 'enjinmel-smtp' ) ); ?>');
        }
    });
})(jQuery);
</script>
```

**Verification:**
- [ ] API key field displays as password by default
- [ ] Show/Hide button toggles visibility correctly
- [ ] Masked value behavior unchanged

---

### Issue #14: Fix Multibyte String Handling

**File:** `includes/class-enjinmel-smtp-log-viewer.php`

**Tasks:**
- [ ] Update `truncate_text()` method at line 530-535
- [ ] Add multibyte-safe truncation
- [ ] Fallback to regular functions if mb_ not available
- [ ] Test with English text
- [ ] Test with Japanese/Chinese characters
- [ ] Test with emoji

**Implementation:**
```php
private static function truncate_text( $text, $length ) {
    // Use multibyte functions if available
    if ( function_exists( 'mb_strlen' ) ) {
        if ( mb_strlen( $text, 'UTF-8' ) <= $length ) {
            return $text;
        }
        return mb_substr( $text, 0, $length, 'UTF-8' ) . '...';
    }
    
    // Fallback to regular functions
    if ( strlen( $text ) <= $length ) {
        return $text;
    }
    return substr( $text, 0, $length ) . '...';
}
```

**Verification:**
```php
// Test cases:
// 1. English: "This is a test message" (should work before and after)
// 2. Japanese: "これはテストメッセージです" (should not break characters)
// 3. Emoji: "Hello 👋 World 🌍" (should preserve emoji)
```

---

## 🧹 PHASE 4: CLEANUP (Future Releases)

**Timeline:** As needed  
**Goal:** Code quality and maintenance

### Low Priority Issues

**Issue #15: Remove Unused Function**
- [ ] Remove `enjinmel_sanitize_query_arg()` from `enjinmel-smtp.php:168-170`
- [ ] Run tests to ensure nothing breaks

**Issue #16: Fix Version Mismatch**
- [ ] Update `readme.txt` line 5 to match plugin header
- [ ] Standardize on WordPress 6.8 (or latest tested)

**Issue #17: Remove Duplicate Export Handler**
- [ ] Delete lines 147-160 in `enjinmel-smtp.php`
- [ ] Verify AJAX export still works
- [ ] Update any documentation

**Issue #10: Decide on Filter Nonce**
- [ ] Either remove nonce field from line 142 in log viewer
- [ ] Or add validation in `get_logs()` method
- [ ] Document decision

**Issue #13: Content-Type Parsing**
- [ ] Research Enginemailer API requirements
- [ ] Test current behavior
- [ ] Implement fix only if needed

**Issue #18: Address Parsing Regex**
- [ ] Review edge cases
- [ ] Consider using WordPress built-in functions
- [ ] Implement only if issues reported

**Issue #19: Fix Indentation**
- [ ] Run `vendor/bin/phpcbf` on entire codebase
- [ ] Commit formatting fixes

---

## 📊 TESTING CHECKLIST

### Phase 1 Testing (Critical)
- [ ] Fresh installation on clean WordPress
- [ ] Upgrade from current version
- [ ] Log viewer page loads without errors
- [ ] API key encryption/decryption works
- [ ] Send test email successfully
- [ ] All log operations work (view, filter, delete, export, clear)
- [ ] No console errors in browser
- [ ] No PHP errors in logs

### Phase 2 Testing (Patch)
- [ ] Logging enabled by default on new install
- [ ] Encryption keys not autoloaded
- [ ] TRUNCATE fallback works
- [ ] Invalid sender email rejected with error message
- [ ] All previous tests still pass

### Phase 3 Testing (Minor)
- [ ] Database index created correctly
- [ ] Query performance improved
- [ ] Uninstall cleans up properly
- [ ] API key field uses password input
- [ ] Multibyte text truncates correctly
- [ ] All previous tests still pass

### Regression Testing
Run after each phase:
- [ ] Send test email
- [ ] View email logs
- [ ] Filter logs by status
- [ ] Filter logs by date range
- [ ] Search logs
- [ ] Delete individual log
- [ ] Bulk delete logs
- [ ] Export logs to CSV
- [ ] Clear all logs
- [ ] Save settings
- [ ] Test with WooCommerce (if available)
- [ ] Test with Contact Form 7 (if available)

---

## 🔧 DEVELOPMENT COMMANDS

```bash
# Run before starting work
composer install
git checkout -b fix/security-audit-phase-1

# During development
vendor/bin/phpcs  # Check code standards
vendor/bin/phpcbf # Auto-fix code standards
vendor/bin/phpunit # Run tests

# Before committing
git add .
git commit -m "fix: implement Phase 1 security fixes

- Fix asset loading paths (Issue #1)
- Implement per-message IV encryption (Issue #2)
- Sanitize and backtick table names (Issue #3)
- Add nonce verification to export (Issue #4)

Refs: SECURITY-AUDIT.md"
```

---

## 📝 DOCUMENTATION UPDATES

After each phase, update:

- [ ] CHANGELOG.md with all fixes
- [ ] README.md if user-facing changes
- [ ] Version numbers in all files
- [ ] Git tags for releases
- [ ] WordPress.org plugin repository (if applicable)

---

## 🤔 QUESTIONABLE ITEMS - DECISION NEEDED

### Issue #20: Legacy Settings Migration

**Question:** Did this plugin replace an older version with option key `enginemail_smtp_settings`?

**If YES:**
- [ ] Implement migration code in `enjinmel_smtp_get_settings()`
- [ ] Test migration with old settings data
- [ ] Add one-time migration notice to admin

**If NO:**
- [ ] Mark as not applicable
- [ ] Document for future reference

**Decision:** ____________ (Yes/No/Unknown)

---

### Issue #21: Transport Field Name

**Question:** Does any downstream code depend on specific transport field naming?

**If YES:**
- [ ] Keep current `'transport' => 'enjinmel_rest'`
- [ ] Document in code comments

**If NO:**
- [ ] Consider standardizing to expected format
- [ ] Test with common plugins

**Decision:** ____________ (Keep current/Change/Test first)

---

## 🎯 RELEASE TARGETS

### v0.1.1 (Patch) - Target: 2 weeks
**Focus:** Critical fixes + high-priority bugs

**Includes:**
- All Phase 1 issues (1-4)
- All Phase 2 issues (5-8)
- Testing and verification
- Documentation updates

**Release Notes:**
```
# EnjinMel SMTP v0.1.1

## Security Fixes
- Implemented per-message IV for encryption (critical security improvement)
- Added nonce verification to log export
- Sanitized table names in SQL queries

## Bug Fixes  
- Fixed asset loading causing log viewer UI to break
- Fixed logging defaulting to OFF on first save
- Added TRUNCATE fallback for restricted hosts
- Added sender email validation at save time
- Set encryption keys to not autoload

## Improvements
- Enhanced backward compatibility for encrypted values
```

### v0.2.0 (Minor) - Target: 1-2 months
**Focus:** Performance + UX improvements

**Includes:**
- All Phase 3 issues (9, 11, 12, 14)
- Selected cleanup items
- Enhanced testing
- Performance benchmarks

**Release Notes:**
```
# EnjinMel SMTP v0.2.0

## Performance
- Added composite index for faster log queries
- Optimized encryption key loading

## Improvements
- Password field for API key with show/hide toggle
- Proper uninstall cleanup
- Multibyte-safe text truncation
- Better internationalization support

## Housekeeping
- Removed unused code
- Updated documentation
- Code standards compliance
```

---

## ✅ SUCCESS CRITERIA

**Phase 1 Complete When:**
- [ ] All 4 critical issues resolved
- [ ] No errors in browser console
- [ ] No PHP warnings/errors
- [ ] All tests passing
- [ ] Code review completed

**Phase 2 Complete When:**
- [ ] All high-priority issues resolved
- [ ] No regression in existing functionality
- [ ] Performance metrics unchanged or improved
- [ ] v0.1.1 released

**Phase 3 Complete When:**
- [ ] All medium-priority issues resolved
- [ ] Performance improvements measured
- [ ] User testing completed
- [ ] v0.2.0 released

---

**Last Updated:** November 2025  
**Status:** Planning Complete - Ready for Implementation
