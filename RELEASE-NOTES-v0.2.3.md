# Release Notes: v0.2.3

**Release Date:** November 17, 2025  
**Priority:** Recommended – addresses WordPress.org plugin check findings before the next release.

---

## 🎯 Overview

Version 0.2.3 is a compliance-focused maintenance release. It aligns the codebase with WordPress.org’s automated plugin checks, sanitizes uninstall-time globals, and refreshes the distributable package so the `/languages` domain path resolves correctly.

---

## 🔧 Key Fixes

1. **Internationalization**
   - Removed the manual `load_plugin_textdomain()` call so WordPress loads translations automatically for the plugin slug.
2. **Plugin Hooks**
   - Added PHPCS ignores when mirroring `wp_mail_failed`, `wp_mail_succeeded`, and `wp_mail_content_type` so the hooks stay compatible with core while satisfying naming conventions.
3. **Uninstall Cleanup**
   - Prefixed uninstall globals (purge flag, table name, sanitized table) to comply with `PrefixAllGlobals` rules before the log table drop.
4. **Packaging**
   - Regenerated `/dist/enjinmel-smtp/` and created the new `enjinmel-smtp-0.2.3.zip` bundle that includes the `languages/` folder referenced by `Domain Path`.

---

## 🗂 Files Updated

- `enjinmel-smtp.php`
- `includes/class-enjinmel-smtp-api-client.php`
- `uninstall.php`
- `README.md`, `readme.txt`, and documentation assets
- `languages/enjinmel-smtp.pot`
- `/dist/enjinmel-smtp/` (packaged snapshot)

## 📦 Release Package

Download or install via `enjinmel-smtp-0.2.3.zip` in `dist/` or the release assets.
