# EnjinMel SMTP - Comprehensive Security & Code Audit

**Audit Date:** November 2025  
**Plugin Version:** 0.1.0  
**Combined Review:** Oracle AI + Secondary AI Analysis

---

## 🚨 CRITICAL - Must Fix Before Production

### 1. Asset Loading Broken (Log Viewer Non-Functional)
**Severity:** CRITICAL - Blocking  
**File:** `includes/class-enjinmel-smtp-log-viewer.php:53,60`

**Issue:**
```php
plugins_url( 'assets/css/log-viewer.css', __DIR__ )
plugins_url( 'assets/js/log-viewer.js', __DIR__ )
```
Resolves to `includes/assets/...` which doesn't exist, causing 404s.

**Fix:**
```php
plugins_url( '../assets/css/log-viewer.css', __FILE__ )
plugins_url( '../assets/js/log-viewer.js', __FILE__ )
// OR
plugins_url( 'assets/css/log-viewer.css', dirname( __DIR__ ) . '/enjinmel-smtp.php' )
```

**Impact:** Log viewer UI completely broken - no CSS/JS loads.

---

### 2. Static IV in AES-256-CBC Encryption
**Severity:** CRITICAL - Security  
**File:** `includes/class-enjinmel-smtp-encryption.php:32-40`

**Issue:**
```php
// Same IV used for all encryption operations
list( $key, $iv ) = $creds;
$cipher = openssl_encrypt( $data, self::ENCRYPTION_METHOD, $key, 0, $iv );
```
Static IV makes encryption deterministic - same plaintext produces same ciphertext.

**Fix:** Implement per-message random IV with versioned format:
```php
public static function encrypt( $data ) {
    if ( '' === $data ) { return ''; }
    $creds = self::get_credentials();
    if ( is_wp_error( $creds ) ) { return $creds; }
    list( $key ) = $creds;

    $iv_len = openssl_cipher_iv_length( self::ENCRYPTION_METHOD );
    $iv     = random_bytes( $iv_len );
    
    $raw = openssl_encrypt( $data, self::ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv );
    if ( $raw === false ) {
        return new WP_Error( 'enjinmel_encryption_failed', __( 'Unable to encrypt value.', 'enjinmel-smtp' ) );
    }
    
    // Version prefix for backward compatibility
    return 'v2:' . base64_encode( $iv . $raw );
}

public static function decrypt( $data ) {
    if ( '' === $data ) { return ''; }
    $creds = self::get_credentials();
    if ( is_wp_error( $creds ) ) { return $creds; }
    list( $key, $legacy_iv ) = $creds;
    
    // New versioned format
    if ( strncmp( $data, 'v2:', 3 ) === 0 ) {
        $blob = base64_decode( substr( $data, 3 ), true );
        if ( $blob === false ) {
            return new WP_Error( 'enjinmel_decryption_failed', __( 'Unable to decrypt value.', 'enjinmel-smtp' ) );
        }
        $iv_len = openssl_cipher_iv_length( self::ENCRYPTION_METHOD );
        $iv     = substr( $blob, 0, $iv_len );
        $raw    = substr( $blob, $iv_len );
        $plain  = openssl_decrypt( $raw, self::ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv );
        return ( $plain === false ) ? new WP_Error( 'enjinmel_decryption_failed', __( 'Unable to decrypt value.', 'enjinmel-smtp' ) ) : $plain;
    }
    
    // Legacy format (backward compatibility)
    $plain = openssl_decrypt( $data, self::ENCRYPTION_METHOD, $key, 0, $legacy_iv );
    return ( $plain === false ) ? new WP_Error( 'enjinmel_decryption_failed', __( 'Unable to decrypt value.', 'enjinmel-smtp' ) ) : $plain;
}
```

**Impact:** Encrypted API keys vulnerable if database compromised.

---

### 3. SQL Injection Risk - Unsanitized Table Names
**Severity:** CRITICAL - Security  
**File:** `includes/class-enjinmel-smtp-log-viewer.php`

**Affected Lines:**
- Line 374: `SELECT COUNT(*) FROM {$table}`
- Line 382: `SELECT * FROM {$table} WHERE`
- Line 415: `DELETE FROM {$table} WHERE`
- Line 476: `SELECT * FROM {$table} WHERE`
- Line 514: `TRUNCATE TABLE {$table}`

**Issue:** Table names interpolated without backticks or consistent sanitization.

**Fix:**
```php
$table = enjinmel_smtp_sanitize_table_name( enjinmel_smtp_active_log_table() );
$count_sql = "SELECT COUNT(*) FROM `{$table}` WHERE {$where_sql}";
$logs_sql = "SELECT * FROM `{$table}` WHERE {$where_sql} ORDER BY timestamp DESC LIMIT %d OFFSET %d";
$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE id IN ({$placeholders})", $log_ids ) );
$deleted = $wpdb->query( "TRUNCATE TABLE `{$table}`" );
```

**Impact:** Potential SQL injection if table name source is compromised.

---

### 4. Export Handler Missing Nonce Verification
**Severity:** HIGH - Security  
**File:** `enjinmel-smtp.php:147-160`

**Issue:**
```php
function enjinmel_smtp_handle_export_logs() {
    if ( ! isset( $_GET['action'] ) || 'enjinmel_smtp_export_logs' !== $_GET['action'] ) {
        return;
    }
    // No nonce verification!
    // No capability check!
    EnjinMel_SMTP_Log_Viewer::ajax_export_logs();
}
```

**Fix:**
```php
function enjinmel_smtp_handle_export_logs() {
    if ( ! isset( $_GET['action'], $_GET['page'], $_GET['nonce'] ) ) {
        return;
    }
    if ( $_GET['action'] !== 'enjinmel_smtp_export_logs' || $_GET['page'] !== 'enjinmel-smtp-logs' ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_GET['nonce'], 'enjinmel_smtp_log_viewer' ) ) {
        wp_die( esc_html__( 'Invalid nonce.', 'enjinmel-smtp' ) );
    }
    EnjinMel_SMTP_Log_Viewer::ajax_export_logs();
}
```

**Alternative:** Remove admin_init handler entirely and use AJAX-only export.

**Impact:** Unauthorized users may be able to export logs via CSRF.

---

## ⚠️ HIGH PRIORITY - Functional Bugs

### 5. Logging Defaults to OFF on First Save
**Severity:** HIGH - Functional  
**File:** `enjinmel-smtp.php:305`

**Issue:**
```php
$output['enable_logging'] = ! empty( $input['enable_logging'] ) ? 1 : 0;
```
If checkbox unchecked on first save, logging permanently disabled (expected default is ON).

**Fix:**
```php
// Preserve existing value or default to enabled (1)
if ( isset( $input['enable_logging'] ) ) {
    $output['enable_logging'] = ! empty( $input['enable_logging'] ) ? 1 : 0;
} else {
    $output['enable_logging'] = isset( $existing['enable_logging'] ) ? $existing['enable_logging'] : 1;
}
```

**Impact:** Users may lose email logs without realizing logging is disabled.

---

### 6. Encryption Keys Stored with autoload=yes
**Severity:** HIGH - Security/Performance  
**File:** `includes/class-enjinmel-smtp-encryption.php:112`

**Issue:**
```php
update_option( 'enjinmel_smtp_encryption_key', $key );
update_option( 'enjinmel_smtp_encryption_iv', $iv );
```
Defaults to `autoload=yes`, loading secrets on every page request.

**Fix:**
```php
add_option( 'enjinmel_smtp_encryption_key', $key, '', 'no' );
add_option( 'enjinmel_smtp_encryption_iv', $iv, '', 'no' );
```

**Impact:** 
- Security: Secrets loaded unnecessarily in shared environments
- Performance: Wasted memory on every request

---

### 7. TRUNCATE May Fail on Restricted Hosts
**Severity:** MEDIUM - Functional  
**File:** `includes/class-enjinmel-smtp-log-viewer.php:514`

**Issue:**
```php
$deleted = $wpdb->query( "TRUNCATE TABLE {$table}" );
```
Some hosts restrict TRUNCATE permissions.

**Fix:**
```php
// Try TRUNCATE first, fallback to DELETE
$deleted = $wpdb->query( "TRUNCATE TABLE `{$table}`" );
if ( false === $deleted ) {
    $deleted = $wpdb->query( "DELETE FROM `{$table}`" );
}
```

**Impact:** "Clear All Logs" feature fails silently on some hosts.

---

### 8. No Sender Email Validation at Save Time
**Severity:** MEDIUM - Functional  
**File:** `enjinmel-smtp.php:303`

**Issue:**
```php
$output['from_email'] = isset( $input['from_email'] ) ? sanitize_email( $input['from_email'] ) : '';
```
Sanitizes but doesn't validate - invalid emails fail only at send time.

**Fix:**
```php
$from_email = isset( $input['from_email'] ) ? sanitize_email( $input['from_email'] ) : '';
if ( ! empty( $from_email ) && ! is_email( $from_email ) ) {
    add_settings_error(
        enjinmel_smtp_option_key(),
        'enjinmel_invalid_from_email',
        __( 'The sender email address is invalid.', 'enjinmel-smtp' ),
        'error'
    );
    $from_email = isset( $existing['from_email'] ) ? $existing['from_email'] : '';
}
$output['from_email'] = $from_email;
```

**Impact:** Users save invalid emails and only discover issue when mail fails.

---

## 🔒 SECURITY IMPROVEMENTS

### 9. API Key Field Should Use type="password"
**Severity:** LOW - Security (UX)  
**File:** Settings page rendering

**Issue:** Plain text input exposes API key to shoulder-surfing.

**Fix:**
```php
<input type="password" id="enjinmel_smtp_api_key" name="..." />
<button type="button" id="toggle_api_key_visibility">Show</button>
```

**Impact:** Physical security risk in shared workspaces.

---

### 10. Filter Form Nonce Created but Never Verified
**Severity:** LOW - Security (Inconsistency)  
**File:** `includes/class-enjinmel-smtp-log-viewer.php:142`

**Issue:**
```php
<?php wp_nonce_field( 'enjinmel_smtp_logs_filter', 'enjinmel_smtp_logs_filter_nonce' ); ?>
```
Nonce field created but never validated in `get_logs()`.

**Fix:** Either validate it or remove it (read-only operations don't strictly need nonces):
```php
// Option 1: Remove unused nonce
// Delete line 142

// Option 2: Validate it
private static function get_logs() {
    // After phpcs:disable comment
    if ( isset( $_GET['s'] ) && ! wp_verify_nonce( $_GET['enjinmel_smtp_logs_filter_nonce'] ?? '', 'enjinmel_smtp_logs_filter' ) ) {
        return array( 'logs' => array(), 'total' => 0, 'per_page' => 20, 'current_page' => 1 );
    }
    // ... rest of function
}
```

**Impact:** Inconsistent security pattern (low risk for read-only).

---

## 🚀 PERFORMANCE IMPROVEMENTS

### 11. Missing Composite Index for Common Queries
**Severity:** MEDIUM - Performance  
**File:** `enjinmel-smtp.php:412-424` (activation)

**Issue:** No index for `(status, timestamp)` used in filtered queries.

**Fix:**
```php
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
```

**Impact:** Slow queries when filtering by status on large log tables.

---

## 🧹 CODE QUALITY & CLEANUP

### 12. Missing Uninstall Cleanup
**Severity:** MEDIUM - Housekeeping  
**File:** `uninstall.php`

**Issue:** Doesn't drop custom table or remove encryption keys.

**Fix:**
```php
<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

global $wpdb;

// Delete plugin settings
delete_option( 'enjinmel_smtp_settings' );
delete_option( 'enjinmel_smtp_encryption_key' );
delete_option( 'enjinmel_smtp_encryption_iv' );

// Drop custom table (if configured to purge on uninstall)
if ( defined( 'ENJINMEL_SMTP_PURGE_LOGS_ON_UNINSTALL' ) && ENJINMEL_SMTP_PURGE_LOGS_ON_UNINSTALL ) {
    $table_name = $wpdb->prefix . 'enjinmel_smtp_logs';
    $wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" );
}
```

**Impact:** Database pollution after plugin deletion.

---

### 13. Content-Type Parsing Includes Charset
**Severity:** LOW - Functional (API compatibility)  
**File:** `includes/class-enjinmel-smtp-api-client.php:179-231`

**Issue:**
```php
$content_type = isset( $headers['content_type'] ) && '' !== $headers['content_type'] ? 
    $headers['content_type'] : apply_filters( 'wp_mail_content_type', 'text/plain' );
// Sends "text/html; charset=UTF-8" to API
$payload['SubmittedContentType'] = $normalized['content_type'];
```

**Potential Fix (if API expects media type only):**
```php
// Extract media type without charset
$content_type_parts = explode( ';', $content_type );
$payload['SubmittedContentType'] = trim( $content_type_parts[0] );
```

**Impact:** Unknown - depends on Enginemailer API requirements. Test before changing.

---

### 14. String Functions Not Multibyte-Safe
**Severity:** LOW - Internationalization  
**File:** `includes/class-enjinmel-smtp-log-viewer.php:531-534`

**Issue:**
```php
private static function truncate_text( $text, $length ) {
    if ( strlen( $text ) <= $length ) {
        return $text;
    }
    return substr( $text, 0, $length ) . '...';
}
```

**Fix:**
```php
private static function truncate_text( $text, $length ) {
    if ( function_exists( 'mb_strlen' ) && mb_strlen( $text, 'UTF-8' ) > $length ) {
        return mb_substr( $text, 0, $length, 'UTF-8' ) . '...';
    }
    if ( strlen( $text ) <= $length ) {
        return $text;
    }
    return substr( $text, 0, $length ) . '...';
}
```

**Impact:** May truncate multibyte characters incorrectly (Japanese, Chinese, emoji).

---

### 15. Unused Helper Function
**Severity:** LOW - Code Cleanup  
**File:** `enjinmel-smtp.php:168-170`

**Issue:**
```php
function enjinmel_sanitize_query_arg( $value ) {
    return sanitize_key( $value );
}
```
Defined but never called.

**Fix:** Remove function or use it in redirect scenarios.

**Impact:** Dead code.

---

### 16. Documentation Version Mismatch
**Severity:** LOW - Documentation  
**Files:**
- `enjinmel-smtp.php:15` - "Tested up to: 6.8.3"
- `readme.txt:5` - "Tested up to: 6.8"

**Fix:** Standardize to `6.8` (or latest tested version).

**Impact:** Plugin repository validation warnings.

---

### 17. Duplicate Export Handling
**Severity:** LOW - Code Cleanup  
**Files:**
- `enjinmel-smtp.php:147-160` (admin_init handler)
- `includes/class-enjinmel-smtp-log-viewer.php:23` (AJAX action)

**Issue:** Export handled via both mechanisms - unnecessary duplication.

**Fix:** Remove admin_init handler, keep AJAX-only:
```php
// DELETE lines 147-160 in enjinmel-smtp.php
// Keep only: add_action( 'wp_ajax_enjinmel_smtp_export_logs', ... )
```

**Impact:** Code complexity, potential confusion.

---

### 18. Simple Regex for Address Parsing
**Severity:** LOW - Edge Case  
**File:** `includes/class-enjinmel-smtp-api-client.php:462`

**Issue:**
```php
if ( preg_match( '/(.*)<(.+)>/', $address, $matches ) ) {
```
May not handle complex display names with special characters correctly.

**Fix:** Use WordPress built-in if available, or enhance regex.

**Impact:** Edge case - unlikely to affect normal usage.

---

### 19. Indentation Inconsistency
**Severity:** TRIVIAL - Code Standards  
**File:** `includes/class-enjinmel-smtp-api-client.php:298,302,326,331,334`

**Issue:** Extra indentation in attachment normalization blocks.

**Fix:** Run `vendor/bin/phpcbf` to auto-fix.

**Impact:** Code readability only.

---

## 🤔 QUESTIONABLE / CONTEXT-DEPENDENT

### 20. Legacy Settings Migration Missing (?)
**Severity:** UNKNOWN - Depends on upgrade path  
**File:** `enjinmel-smtp.php:108`

**Issue:** No migration from `enginemail_smtp_settings` to `enjinmel_smtp_settings`.

**Context:** Only relevant if this plugin replaces an older version with different option key.

**Fix (if needed):**
```php
function enjinmel_smtp_get_settings( $default_value = array() ) {
    $settings = get_option( enjinmel_smtp_option_key(), null );
    
    // Migrate from legacy option key
    if ( null === $settings ) {
        $legacy = get_option( 'enginemail_smtp_settings', null );
        if ( null !== $legacy && is_array( $legacy ) ) {
            $settings = $legacy;
            update_option( enjinmel_smtp_option_key(), $settings );
            delete_option( 'enginemail_smtp_settings' );
        }
    }
    
    if ( ! is_array( $settings ) ) {
        return $default_value;
    }
    
    return $settings;
}
```

**Decision:** Verify if legacy migration is needed for your upgrade path.

---

### 21. Transport Field Name
**Severity:** LOW - Compatibility  
**File:** `enjinmel-smtp.php:383`

**Issue:** Uses `'transport' => 'enjinmel_rest'` in wp_mail_succeeded hook.

**Alternative Expectation:** `'legacy_transport' => 'enginemail_rest'`

**Context:** Only matters if downstream code expects specific field name.

**Decision:** Keep current implementation unless compatibility issue identified.

---

## 📋 PRIORITY SUMMARY

| Priority | Count | Must Fix Before |
|----------|-------|-----------------|
| **Critical** | 4 | Any production use |
| **High** | 4 | v0.1.1 patch release |
| **Medium** | 4 | v0.2.0 minor release |
| **Low** | 9 | Future releases |
| **Questionable** | 2 | Assess based on context |

---

## ✅ IMPLEMENTATION CHECKLIST

### Immediate (Pre-Production)
- [ ] Fix asset paths (Issue #1)
- [ ] Implement per-message IV encryption (Issue #2)
- [ ] Sanitize and backtick all table names (Issue #3)
- [ ] Add nonce/capability to export handler (Issue #4)

### Patch Release (v0.1.1)
- [ ] Fix logging default behavior (Issue #5)
- [ ] Set encryption keys autoload=no (Issue #6)
- [ ] Add TRUNCATE fallback (Issue #7)
- [ ] Validate sender email at save (Issue #8)

### Minor Release (v0.2.0)
- [ ] Add composite index (Issue #11)
- [ ] Implement proper uninstall (Issue #12)
- [ ] Use type="password" for API key (Issue #9)
- [ ] Fix multibyte string handling (Issue #14)

### Cleanup
- [ ] Remove unused function (Issue #15)
- [ ] Fix version mismatch (Issue #16)
- [ ] Remove duplicate export handler (Issue #17)
- [ ] Decide on filter nonce (Issue #10)

---

**End of Audit**
