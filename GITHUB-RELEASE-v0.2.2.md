# GitHub Release Instructions for v0.2.2

## Quick Steps

1. **Go to GitHub Releases:**
   ```
   https://github.com/liewcf/enjinmel-smtp/releases/new?tag=v0.2.2
   ```

2. **Fill in the form:**
   - **Tag:** `v0.2.2` (already created and pushed ✓)
   - **Release title:** `v0.2.2 - Critical Bug Fixes`
   - **Description:** Use the content below
   - **Upload Asset:** `dist/enjinmel-smtp-0.2.2.zip`
   - **Pre-release:** Uncheck (this is stable)
   - **Latest release:** Check

3. **Click "Publish release"**

---

## Release Title

```
v0.2.2 - Critical Bug Fixes
```

---

## Release Description

Copy and paste this into the description field:

```markdown
# 🔥 Critical Bug Fixes

Version 0.2.2 resolves critical issues preventing email delivery in v0.2.0 and v0.2.1.

**Priority:** CRITICAL - All users should upgrade immediately.

---

## What's Fixed

### 1. Double-Encryption Bug ✅
**Problem:** API keys became corrupted when settings were saved multiple times, causing authentication failures.

**Solution:**
- ✅ Added automatic detection to prevent re-encryption
- ✅ Auto-repair of existing corrupted keys on plugin load
- ✅ One-time migration runs transparently
- ✅ No user action required

### 2. API V2 Compatibility ✅
**Problem:** Plugin was sending unsupported fields causing 500 Internal Server Errors.

**Solution:**
- ✅ Removed `SubmittedContentType`, `IsHtmlContent`, `ReplyToEmail` fields
- ✅ Made `SenderName` optional (only sent when not empty)
- ✅ Added default values for empty subject/message fields
- ✅ Full compliance with [EnjinMel V2 API spec](https://enginemailer.zendesk.com/hc/en-us/articles/23132996552473)

---

## 📥 Installation

### WordPress Admin (Recommended)
1. Download `enjinmel-smtp-0.2.2.zip` below
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload and activate

### Manual
1. Extract zip to `/wp-content/plugins/enjinmel-smtp/`
2. Activate in WordPress admin

---

## ✅ Verification

After updating:

1. **Check version:** Should show "0.2.2" in Plugins page
2. **Test email:** Go to **EnjinMel SMTP → Settings** → Send Test Email
3. **Check logs:** Go to **EnjinMel SMTP → Email Logs** → Status should be "Sent"

**Auto-repair runs automatically** - if you had issues, they should be fixed immediately without any action needed!

---

## 🔄 Upgrade Path

- **From v0.2.1:** Direct upgrade, auto-repair included
- **From v0.2.0:** Includes all v0.2.1 fixes + auto-repair
- **From v0.1.0:** All security, performance, and API fixes included

---

## 📋 Files Changed

- `enjinmel-smtp.php` - Added double-encryption detection and auto-repair
- `includes/class-enjinmel-smtp-api-client.php` - API V2 compliance
- `CHANGELOG.md` - Updated changelog
- `readme.txt` - Updated for WordPress.org

---

## 📚 Documentation

- **Full Release Notes:** [RELEASE-NOTES-v0.2.2.md](https://github.com/liewcf/enjinmel-smtp/blob/main/RELEASE-NOTES-v0.2.2.md)
- **Bug Fix Summary:** [BUGFIX-SUMMARY-v0.2.2.md](https://github.com/liewcf/enjinmel-smtp/blob/main/BUGFIX-SUMMARY-v0.2.2.md)
- **Changelog:** [CHANGELOG.md](https://github.com/liewcf/enjinmel-smtp/blob/main/CHANGELOG.md)

---

## 🐛 Known Issues

None. All critical issues are resolved.

**Limitation:** Reply-To headers not supported (V2 API doesn't document this field).

---

## 💬 Support

- **Issues:** [GitHub Issues](https://github.com/liewcf/enjinmel-smtp/issues)
- **Email Logs:** Check **EnjinMel SMTP → Email Logs** for detailed errors
- **Requirements:** WordPress 5.3+, PHP 7.4+, Valid EnjinMel API key

---

## 🙏 Thank You

Thanks to all users who reported issues and helped with testing. Your feedback was invaluable!

---

**This is a production-ready release.** 🚀
```

---

## Upload Asset

1. **Click "Attach binaries"** or drag and drop
2. **Upload:** `/Users/cheonfongliew/Code/enjinmel-smtp/dist/enjinmel-smtp-0.2.2.zip`
3. **File size:** ~196KB
4. **Filename:** `enjinmel-smtp-0.2.2.zip`

---

## Settings

- ✅ **Set as the latest release** (CHECK THIS)
- ❌ **Set as a pre-release** (UNCHECK THIS - it's stable)
- Target: `main` branch

---

## After Publishing

### 1. Verify the Release
- Check: https://github.com/liewcf/enjinmel-smtp/releases/tag/v0.2.2
- Verify download link works
- Confirm release notes display correctly

### 2. Test the Download
```bash
# Download and verify
wget https://github.com/liewcf/enjinmel-smtp/releases/download/v0.2.2/enjinmel-smtp-0.2.2.zip
unzip -t enjinmel-smtp-0.2.2.zip
```

### 3. Update Documentation (Optional)
- Update README.md if needed
- Update project website
- Announce on social media

### 4. Monitor
- Watch for new issues
- Check download statistics
- Gather user feedback

---

## Troubleshooting

### If Tag Doesn't Exist
```bash
# Create and push tag
cd /Users/cheonfongliew/Code/enjinmel-smtp
git tag -a v0.2.2 -m "Release v0.2.2"
git push origin v0.2.2
```

### If Release Fails
1. Save the release as **Draft** first
2. Review and edit
3. Publish when ready

### To Edit After Publishing
1. Go to the release page
2. Click **Edit release**
3. Make changes
4. Click **Update release**

---

## Alternative: Using GitHub CLI

If you have `gh` CLI installed:

```bash
cd /Users/cheonfongliew/Code/enjinmel-smtp

gh release create v0.2.2 \
  --title "v0.2.2 - Critical Bug Fixes" \
  --notes-file RELEASE-NOTES-v0.2.2.md \
  --latest \
  dist/enjinmel-smtp-0.2.2.zip
```

---

## Quick Link

**Direct Release Creation Link:**
https://github.com/liewcf/enjinmel-smtp/releases/new?tag=v0.2.2

---

**Ready to release!** 🎉
