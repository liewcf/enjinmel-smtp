# Release Notes: v0.2.2

**Release Date:** November 7, 2025  
**Priority:** CRITICAL - Recommended for all users

---

## 🎯 Overview

Version 0.2.2 is a critical bug fix release that resolves a double-encryption issue and completes the API V2 migration started in v0.2.1.

This release fixes all issues preventing email delivery and makes the plugin fully compatible with the EnjinMel REST API V2 specification.

---

## 🔥 Critical Fixes

### 1. Double-Encryption Bug (CRITICAL)
**Problem:** API keys could become corrupted (double-encrypted) when settings were saved multiple times, causing authentication failures.

**Impact:** 
- API returns "401 Unauthorized" 
- All email sends fail
- Users see "Unknown error" or "Request failed" messages

**Solution:**
- ✅ Added detection to prevent re-encryption of already encrypted values
- ✅ Automatic repair of existing corrupted keys on plugin load
- ✅ One-time migration runs transparently
- ✅ No user action required

**Technical Details:**
The bug occurred when an encrypted API key (starting with `v2:`) was re-submitted without the masking asterisks. The code would encrypt it again, creating a double-encrypted value that couldn't be properly decrypted.

### 2. API V2 Compatibility (from v0.2.1)
**Problem:** Plugin was sending fields not supported by EnjinMel V2 API, causing 500 Internal Server Errors.

**Solution:**
- ✅ Removed `SubmittedContentType` field (not in V2 spec)
- ✅ Removed `IsHtmlContent` field (not in V2 spec)
- ✅ Removed `ReplyToEmail` field (not documented in V2 spec)
- ✅ Made `SenderName` optional (only sent when not empty)
- ✅ Added default values for empty subject and message fields

---

## 📦 What's Included

### Automatic Fixes
1. **Double-Encryption Detection** - Detects `v2:` prefix in submitted values
2. **Auto-Repair Function** - `enjinmel_smtp_fix_double_encryption()`
3. **Migration Logic** - Runs once per installation automatically

### Enhanced Validation
1. **API Key Handling** - Smarter encryption state detection
2. **Empty Field Defaults** - Subject: "(no subject)", Message: " "
3. **Optional Fields** - SenderName only included when not empty

### API V2 Compliance
The plugin now sends **only** fields documented in the official V2 API:

**Required:**
- `ToEmail` - Recipient email address(es)
- `SenderEmail` - Verified sender email

**Optional:**
- `Subject` - Email subject
- `SenderName` - Sender name
- `SubmittedContent` - Email body
- `CampaignName` - Campaign identifier
- `TemplateId` - Template ID
- `Attachments` - File attachments
- `CCEmails` - CC recipients (max 10)
- `BCCEmails` - BCC recipients (max 3)
- `SubstitutionTags` - Template variables

---

## 🔧 Files Changed

### Core Plugin
**`enjinmel-smtp.php`**
- Added `v2:` prefix detection in settings sanitization
- Added `enjinmel_smtp_fix_double_encryption()` function (47 lines)
- Added automatic migration logic in `enjinmel_smtp_get_settings()`
- Version bumped to 0.2.2

### API Client
**`includes/class-enjinmel-smtp-api-client.php`**
- Removed unsupported V2 API fields
- Added default values for empty fields
- Made SenderName conditional
- Improved API payload building logic

### Documentation
- **`CHANGELOG.md`** - Added v0.2.2 section
- **`readme.txt`** - Updated changelog and stable tag
- **`BUGFIX-SUMMARY-v0.2.2.md`** - Detailed technical analysis
- **`RELEASE-NOTES-v0.2.1.md`** - Previous release notes

---

## 📥 Installation

### Method 1: WordPress Admin (Recommended)
1. Download `enjinmel-smtp-0.2.2.zip` from the release
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip file
4. Click **Activate Plugin** (or it will auto-update)

### Method 2: Manual Installation
1. Extract `enjinmel-smtp-0.2.2.zip`
2. Upload to `/wp-content/plugins/enjinmel-smtp/`
3. Activate in WordPress admin

### Method 3: Git
```bash
cd /path/to/wp-content/plugins/enjinmel-smtp
git pull origin main
git checkout v0.2.2
```

---

## ✅ Verification

After updating, verify everything works:

1. **Check Version**
   - Go to **Plugins** page
   - Should show "Version 0.2.2"

2. **Test Email Sending**
   - Go to **EnjinMel SMTP → Settings**
   - Click **Send Test Email**
   - Enter your email address
   - Should receive success message (not error)

3. **Check Email Logs**
   - Go to **EnjinMel SMTP → Email Logs**
   - Latest entries should show "Sent" status
   - No "Failed" with "Unknown error"

4. **Verify Auto-Repair** (if you had issues)
   - Email sending should work immediately
   - No need to re-enter API key
   - Auto-repair runs silently

---

## 🔄 Upgrade Path

### From v0.2.1
- ✅ Direct upgrade
- ✅ Auto-repair of double-encrypted keys
- ✅ No configuration changes needed

### From v0.2.0
- ✅ Includes all v0.2.1 fixes
- ✅ Auto-repair of double-encrypted keys
- ✅ Full V2 API compatibility

### From v0.1.0
- ✅ All security fixes from v0.2.0
- ✅ All performance improvements
- ✅ All API V2 fixes
- ⚠️ Review settings after upgrade

---

## 🐛 Known Issues

None. All critical issues from v0.2.0 and v0.2.1 are resolved.

### Limitations
- **Reply-To Headers:** V2 API does not document a Reply-To field. If needed, configure via EnjinMel templates.

---

## 🔐 Security Notes

- API keys remain encrypted at rest
- Auto-repair uses secure decryption
- No plaintext API keys in logs
- Migration marked complete after first run

---

## 📚 API Documentation Reference

This release fully implements the official EnjinMel REST API V2 specification:
https://enginemailer.zendesk.com/hc/en-us/articles/23132996552473

---

## 🎯 What's Next?

### Future Enhancements
- WP-CLI commands for email management
- Dashboard widget with sending statistics
- Bulk email testing tools
- Enhanced template support
- Reply-To field support (if API adds it)

---

## 💬 Support

### Reporting Issues
- **GitHub Issues:** https://github.com/liewcf/enjinmel-smtp/issues
- **Email Logs:** Check logs for detailed error messages
- **Debug Mode:** Enable WP_DEBUG for detailed logging

### Requirements
- WordPress 5.3 or higher
- PHP 7.4 or higher
- Valid EnjinMel API key
- Verified sending domain in EnjinMel portal

### Documentation
- Plugin settings: **EnjinMel SMTP → Settings**
- Email logs: **EnjinMel SMTP → Email Logs**
- EnjinMel Portal: https://portal.enginemailer.com

---

## 🙏 Acknowledgments

Thank you to all users who reported issues and helped with testing during the v0.2.1 rollout. Your feedback was invaluable in identifying and resolving the double-encryption bug.

---

## 📝 Changelog Summary

### v0.2.2 (2025-11-07)
- **Fixed:** Double-encryption bug causing API key corruption
- **Fixed:** Automatic detection and repair of corrupted keys
- **Changed:** Enhanced settings sanitization with encryption state detection

### v0.2.1 (2025-11-07)
- **Fixed:** API V2 compatibility (removed unsupported fields)
- **Fixed:** Made SenderName optional
- **Fixed:** Added default values for empty fields

### v0.2.0 (2025-11-01)
- **Security:** Per-message random IV encryption
- **Security:** SQL injection prevention
- **Performance:** Composite database index
- **Added:** Password-masked API key field

---

**This is a production-ready release.** All critical issues are resolved and the plugin is fully functional with the EnjinMel REST API V2.

🚀 **Ready to deploy!**
