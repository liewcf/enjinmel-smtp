# Release Notes: v0.2.1

**Release Date:** 2025-11-07  
**Priority:** CRITICAL - All users should update immediately

## Overview

Version 0.2.1 fixes a critical API compatibility issue that prevented emails from being sent. The plugin was sending fields not supported by the EnjinMel REST API V2, causing 500 Internal Server Errors.

## The Problem

After deploying v0.2.0, all email sends were failing with "Unknown error" messages. Investigation revealed:

1. **Root Cause:** Plugin was sending unsupported fields to the EnjinMel V2 API
2. **API Response:** HTTP 500 - "Object reference not set to an instance of an object"
3. **User Impact:** Complete email delivery failure

## The Fix

### Removed Unsupported Fields

The following fields were removed from the API payload as they are **not supported** in the V2 API:

- ❌ `SubmittedContentType` - Not in V2 specification
- ❌ `IsHtmlContent` - Not in V2 specification  
- ❌ `ReplyToEmail` - Not documented in V2 API

### API V2 Compliance

The plugin now sends **only the fields documented** in the official V2 API:

**Required Fields:**
- `ToEmail` - Recipient email address(es)
- `SenderEmail` - Sender email (must match verified domain)

**Optional Fields:**
- `Subject` - Email subject (defaults to "(no subject)" if empty)
- `SenderName` - Sender name (only sent if not empty)
- `SubmittedContent` - Email body (defaults to " " if empty)
- `CampaignName` - Campaign identifier (if configured)
- `TemplateId` - Template ID (if configured)
- `Attachments` - File attachments (if present)
- `CCEmails` - CC recipients (if present, max 10)
- `BCCEmails` - BCC recipients (if present, max 3)
- `SubstitutionTags` - Dynamic variables for templates (if present)

### Additional Improvements

1. **Empty Field Handling:** Added default values for empty subject and message
2. **Optional SenderName:** Only included when not empty to prevent null reference errors
3. **HTML Support:** HTML content now sent directly in SubmittedContent (API auto-detects)

## Files Changed

- `includes/class-enjinmel-smtp-api-client.php` - Fixed API payload building
- `enjinmel-smtp.php` - Version bump to 0.2.1
- `readme.txt` - Updated changelog
- `CHANGELOG.md` - Documented changes

## Testing

### Before Fix
```json
{
  "ToEmail": "test@example.com",
  "Subject": "Test",
  "SenderEmail": "sender@example.com",
  "SenderName": "",
  "SubmittedContent": "Hello",
  "SubmittedContentType": "text/plain",  ← NOT SUPPORTED
  "IsHtmlContent": false,                 ← NOT SUPPORTED
  "ReplyToEmail": "reply@example.com"     ← NOT DOCUMENTED
}
```
**Result:** HTTP 500 - Internal Server Error

### After Fix
```json
{
  "ToEmail": "test@example.com",
  "Subject": "Test",
  "SenderEmail": "sender@example.com",
  "SubmittedContent": "Hello"
}
```
**Result:** HTTP 200 - Success

## Installation

### Option 1: Upload via WordPress Admin
1. Download `enjinmel-smtp-0.2.1.zip`
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip file
4. Activate (or it will auto-update)

### Option 2: Manual Installation
1. Extract `enjinmel-smtp-0.2.1.zip`
2. Upload to `/wp-content/plugins/enjinmel-smtp/`
3. Activate in WordPress admin

## Verification

After updating, verify the fix:

1. Go to **EnjinMel SMTP → Settings**
2. Click **Send Test Email**
3. Enter your email address
4. Should receive "Email sent successfully" (not "Unknown error")
5. Check **Email Logs** to confirm status is "Sent" (not "Failed")

## API Documentation Reference

This fix implements the official EnjinMel REST API V2 specification:
https://enginemailer.zendesk.com/hc/en-us/articles/23132996552473

## Breaking Changes

None. This is a bug fix release with no breaking changes.

## Known Limitations

- **Reply-To Headers:** The V2 API does not document a Reply-To field. If your emails need custom Reply-To addresses, this may need to be configured via EnjinMel templates.

## Support

For issues or questions:
- GitHub: https://github.com/liewcf/enjinmel-smtp
- Check logs: **EnjinMel SMTP → Email Logs**

---

**Thank you for your patience while we resolved this critical issue!**
