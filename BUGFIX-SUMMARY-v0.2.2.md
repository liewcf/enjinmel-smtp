# Bug Fix Summary: v0.2.2

**Date:** 2025-11-07  
**Priority:** CRITICAL  
**Issue:** Double-encryption bug causing API key corruption

---

## The Problem

After deploying v0.2.0/v0.2.1, some users experienced API authentication failures with error:
```json
{
  "StatusCode": "401",
  "Status": "Unauthorized", 
  "ErrorMessage": "Authorization failed"
}
```

### Root Cause Analysis

The API key was being **double-encrypted** due to a flaw in the settings sanitization logic:

1. **Initial Save:** User enters plain API key `15c658ba-55f8-4757-b...` → encrypted to `v2:23KuDwe...45Tg==`
2. **Database Storage:** Stored as `v2:23KuDwe...45Tg==`
3. **Form Display:** Key is masked as `15c6****` (correct)
4. **Problem on Re-save:** If the encrypted value `v2:23KuDwe...45Tg==` somehow gets submitted (without asterisks):
   - Code checks: "Does it contain asterisks?" → NO
   - Code thinks: "Must be a new key!" 
   - **Encrypts again** → `v2:uMndy3BV4HLmaNRC/...` (double-encrypted!)
5. **API Call Fails:** When decrypted once, returns `v2:23KuDwe...45Tg==` (still encrypted) instead of the real key

### How It Happened

The bug occurred when:
- Settings page was saved multiple times
- Browser auto-fill submitted encrypted values
- Form validation errors caused re-submission with encrypted values
- Any scenario where the encrypted value bypassed the masking logic

---

## The Fix

### 1. Prevention: Detect Already-Encrypted Values

**File:** `enjinmel-smtp.php` (settings sanitization)

```php
// OLD CODE (Bug)
if ( strpos( $submitted_key, '*' ) !== false ) {
    // Keep existing encrypted key
} else {
    // Encrypt it (BUG: also encrypts already-encrypted values!)
    $encrypted = EnjinMel_SMTP_Encryption::encrypt( $submitted_key );
}

// NEW CODE (Fixed)
if ( strpos( $submitted_key, '*' ) !== false ) {
    // Keep existing encrypted key
} elseif ( strncmp( $submitted_key, 'v2:', 3 ) === 0 ) {
    // Already encrypted - prevent double-encryption!
    $output['api_key'] = $submitted_key;
} else {
    // New plain key - encrypt it
    $encrypted = EnjinMel_SMTP_Encryption::encrypt( $submitted_key );
}
```

**What this does:**
- Checks if submitted value starts with `v2:` (our encryption prefix)
- If yes, keeps it as-is instead of re-encrypting
- Prevents double-encryption from happening

### 2. Automatic Repair: Fix Existing Corrupted Keys

**File:** `enjinmel-smtp.php` (new functions)

Added two new functions:

#### A. `enjinmel_smtp_fix_double_encryption()`
```php
function enjinmel_smtp_fix_double_encryption( array $settings ) {
    // Decrypt once
    $decrypted_once = decrypt( $settings['api_key'] );
    
    // Check if still encrypted (has v2: prefix)
    if ( starts_with( $decrypted_once, 'v2:' ) ) {
        // Decrypt again
        $decrypted_twice = decrypt( $decrypted_once );
        
        // Now we have the real key - re-encrypt properly
        $properly_encrypted = encrypt( $decrypted_twice );
        $settings['api_key'] = $properly_encrypted;
    }
    
    return $settings;
}
```

#### B. Automatic Migration in `enjinmel_smtp_get_settings()`
```php
// Runs once on plugin load
if ( ! get_option( 'enjinmel_smtp_double_encryption_fixed' ) ) {
    $fixed = enjinmel_smtp_fix_double_encryption( $settings );
    if ( $fixed !== $settings ) {
        update_option( ..., $fixed );
    }
    update_option( 'enjinmel_smtp_double_encryption_fixed', true );
}
```

**What this does:**
- Automatically detects corrupted keys on plugin load
- Decrypts twice to get the real key
- Re-encrypts properly with single layer
- Runs only once per installation (marked as fixed)
- Completely transparent to users

---

## Impact

### Before Fix
- ❌ Users with double-encrypted keys: API auth fails
- ❌ Manual intervention required (clear key, re-enter)
- ❌ Difficult to diagnose (looks like wrong API key)

### After Fix
- ✅ Prevention: Cannot double-encrypt anymore
- ✅ Auto-repair: Existing corrupted keys fixed automatically
- ✅ Transparent: Works without user action
- ✅ No data loss: Original keys recovered

---

## Testing

### Test Case 1: Prevent Double-Encryption
1. Enter new API key → Save
2. Open settings again → Save again
3. **Expected:** Key remains single-encrypted
4. **Result:** ✅ Pass

### Test Case 2: Auto-Repair Existing
1. Simulate double-encrypted key in database
2. Load settings page
3. **Expected:** Key automatically fixed
4. **Result:** ✅ Pass

### Test Case 3: Send Email
1. After fix applied
2. Send test email
3. **Expected:** Success (not 401 Unauthorized)
4. **Result:** ✅ Pass

---

## Files Changed

1. **enjinmel-smtp.php**
   - Added `v2:` prefix check in settings sanitization
   - Added `enjinmel_smtp_fix_double_encryption()` function
   - Added auto-repair logic in `enjinmel_smtp_get_settings()`

2. **CHANGELOG.md** - Documented changes

3. **readme.txt** - Updated changelog

---

## Deployment

### New Package
```
/dist/enjinmel-smtp-0.2.2.zip (196KB)
```

### Version
- v0.2.1 → v0.2.2

### Installation
Users can simply update the plugin:
- Upload new zip file
- Or wait for auto-update (if configured)
- **No manual intervention needed** - auto-repair handles corrupted keys

---

## Lessons Learned

1. **Always validate encryption state** - Don't assume submitted values are plain text
2. **Add defensive checks** - Check for encryption markers before re-encrypting
3. **Provide migration paths** - Auto-repair for existing issues
4. **Test edge cases** - Form re-submissions, browser auto-fill, validation errors
5. **Make fixes transparent** - Users shouldn't need to do anything

---

## Verification

To verify the fix is working:

1. **Check version:** Should show 0.2.2 in WordPress admin
2. **Send test email:** Should succeed without 401 error
3. **Check logs:** Status should be "Sent" not "Failed"
4. **No user action needed:** Auto-repair happens automatically

---

**Status:** ✅ FIXED AND TESTED  
**Risk:** LOW - No breaking changes, backwards compatible  
**User Impact:** POSITIVE - Fixes authentication issues automatically
