---
title: Work Changelog
description: Dated notes on changed files, deliverables, tooling, checks, and verification.
doc_type: work_log
status: active
created: 2026-07-03
updated: 2026-08-19
tags:
  - project-memory
  - changelog
  - work-log
  - verification
audience:
  - agent
  - maintainer
related:
  - PROJECT_CONTEXT.md
  - DECISIONS.md
  - TASKS.md
---

# Work Changelog

## 2026-08-19

- Verified EnjinMel SMTP `0.2.5` against WordPress `7.1-RC4` (final 7.1 release scheduled 2026-08-19; latest stable at test time was 7.0.4).
- Research: reviewed the 7.1 field guide/dev notes surface (Icons API, speculative loading config, media library infinite scroll, jQuery UI 1.14.2, KSES `autofocus` on `dialog`); none touch the plugin's integration points (`pre_wp_mail`, WP HTTP API, Settings API, `dbDelta`, admin menus, `wp_ajax_*`).
- Test environment: Docker (OrbStack) with `wordpress:beta-php8.3` (7.1-RC4, PHP 8.3.33) and `mysql:8.4`.
- Live-site smoke tests all passed: plugin activation with `WP_DEBUG` on and zero notices/warnings in `debug.log`; log table `wp_enjinmel_smtp_logs` created on activation; settings sanitize + `v2:` encryption round-trip; settings page and Email Logs page render over HTTP with localized script data and nonces; Send Test Email AJAX flow returns the expected API-rejection error (fake key reaches the real Enginemailer endpoint and the error is surfaced through `wp_send_json_error`); failed sends logged with correct statuses; CSV export and Clear All Logs AJAX actions work.
- PHPUnit against 7.1-RC4: `51 tests, 164 assertions, 0 failures` — identical counts to the WordPress 7.0 verification. 8 tests flagged risky due to a PHP 8.2+ dynamic-property deprecation in test code only (`tests/unit/AJAX_Handler_Test.php` assigns an undeclared `$admin_user` property); not a WordPress 7.1 incompatibility and no plugin code is affected.
- Test library setup note: `wp-phpunit/wp-phpunit` has no 7.1 package yet, so the WordPress test library was built from the `wordpress-develop` `7.1` branch (`7.1-RC4-63322-src`) with `ABSPATH` pointed at the RC4 core inside the container.
- Verification run: full PHPUnit (Docker-backed), PHPCS clean, live-site HTTP/AJAX/DB probes. No code changes required.
- Fixed the test-only PHP 8.2+ dynamic-property deprecation: declared `public $admin_user;` (with PHPDoc) in `tests/unit/AJAX_Handler_Test.php`. Re-ran the full suite against WordPress 7.1-RC4/PHP 8.3: `OK (51 tests, 164 assertions)` with zero risky tests (previously 8); `--filter AJAX_Handler_Test` confirmed `OK (8 tests, 33 assertions)` with no deprecation output. `php -l` and PHPCS clean on the edited file. Test-only change; no plugin code touched.

## 2026-07-16

- Confirmed EnjinMel SMTP `0.2.5` is published on WordPress.org.
- Diagnosed absent directory artwork: display assets must be committed to the Plugin Directory SVN checkout's top-level `assets/` directory, not the plugin's `trunk/assets/` runtime directory or the release ZIP.
- Prepared and committed `assets/icon.svg`, `assets/screenshot-1.png`, and `assets/screenshot-2.png` in WordPress.org SVN revision `3609733`.
- Prepared valid PNG fallbacks and headers in the same checkout: `banner-1544x500.png` (1544x500), `banner-772x250.png` (772x250), and `icon-256x256.png` (256x256). They are staged locally but not yet committed; public banner and PNG fallback verification is therefore pending.

## 2026-07-15

- Read the 2026-07-14 manual WordPress.org review for the `0.2.5` submission. The remaining findings were two PHP-rendered settings-page `<script>` blocks and an incomplete Enginemailer external-service disclosure.
- Added `assets/js/settings.js` for the API-key visibility and Send Test Email interactions. `enjinmel-smtp.php` now enqueues it only on the plugin settings screen and localizes the AJAX URL, action, nonce, and translated strings. Removed both inline script blocks and the hidden nonce input from PHP markup.
- Added a complete `== External services ==` section to `readme.txt` covering when the Enginemailer API is contacted, the default data sent, attachment handling, purpose, and official Terms of Service and Privacy Policy links.
- Added `tests/unit/Admin_Assets_Test.php` with coverage for settings-screen-only enqueuing, localized runtime data, and script-free rendered settings markup.
- Verification: full PHPCS clean; PHP syntax clean for edited PHP and the new test; `node --check assets/js/settings.js` clean; Docker-backed WordPress 7.0/PHP 8.1/MySQL 8.4 PHPUnit run passed `51 tests, 164 assertions` with `WP_DEBUG` enabled.
- Browser verification on a clean disposable WordPress 6.9/PHP 8.1 site with `WP_DEBUG` enabled confirmed `settings.js?ver=0.2.5` loaded with HTTP 200, Show/Hide toggled the API-key field, and Send Test Email completed its AJAX flow and displayed the expected missing-API-key failure state without a JavaScript exception.
- Rebuilt and inspected `dist/enjinmel-smtp.zip`. ZIP integrity passed, no dev/hidden files were present, both cited PHP files contained no script/style tags, and the external-services section and legal links were present. Exact artifact: 205,903 bytes; SHA-256 `c2f72fd4bcfe5748476e3470f933ac6f59ea8e882f41c26e51414c53545dfd01`.
- Extracted the exact ZIP and ran `npx pressship verify` against the plugin directory. Readme validation had no findings and Plugin Check ended with `Success: Checks complete. No errors found.` The only warning was a deprecation in Pressship's bundled WP-CLI dependency, not a plugin finding.

## 2026-07-13

- Investigated the failed WordPress.org automated scan and confirmed the uploaded `dist/enjinmel-smtp.zip` was a stale 2025 archive. It contained `.gitattributes`, `.gitignore`, `Tested up to: 6.8`, and the older log-viewer SQL-warning annotations.
- Rebuilt `dist/enjinmel-smtp.zip` from commit `3d49872` using only production plugin paths under the `enjinmel-smtp/` directory.
- Verified the replacement ZIP contains no hidden files, declares `Tested up to: 7.0`, includes the justified `PluginCheck.Security.DirectDB.UnescapedDBParameter` annotations, passes `unzip -t`, and passes PHPCS.
- Extracted the exact replacement ZIP and ran `npx pressship verify` against it. Readme validation had no findings and Plugin Check reported: `Success: Checks complete. No errors found.` The only warning was a deprecation notice from Pressship's bundled WP-CLI dependency, not from the plugin.
- Replacement artifact: `dist/enjinmel-smtp.zip` (204,616 bytes; SHA-256 `10008c499904f7f9a8a1b15b9dd9d96bbb069828b7ce06d2c135fa530d9f1554`).

## 2026-07-03

- Resolved 27 WordPress.org Plugin Check warnings for the `0.2.5` submission (commit `3d49872`, pushed to `origin/main`):
  - `includes/class-enjinmel-smtp-log-viewer.php`: added `phpcs:disable`/`phpcs:enable` block around read-only `$_GET` filter/pagination reads in `get_logs()` (matching the existing `render_filters()` pattern); appended `PluginCheck.Security.DirectDB.UnescapedDBParameter` to 6 existing `phpcs:ignore` comments.
  - `enjinmel-smtp.php`: moved the `wp_mail_succeeded` `NonPrefixedHooknameFound` ignore inline onto the hookname line (the multi-line suppression was covering `do_action(` but not the flagged hookname token); appended the DirectDB code to 3 existing ignores.
  - `includes/class-enjinmel-smtp-logging.php`: appended the DirectDB code to 4 existing ignores.
  - `uninstall.php`: appended the DirectDB code to 1 existing ignore.
  - `readme.txt`: trimmed the `0.2.3` upgrade notice from 306 to 255 chars (under the 300-char limit).
  - No executable PHP logic changed; comment-only plus a readme text trim.
- Submitted `0.2.5` to WordPress.org via `npx pressship publish` (slug `enjinmel-smtp`, Plugin ID 335769, status: Awaiting Review). Used 24 `--ignore` globs to exclude dev-only files (`vendor/`, `tests/`, `specs/`, `scripts/`, `docs/`, `templates/`, `.github/`, `dist/`, `node_modules/`, `composer.json`, `composer.lock`, `phpunit.xml`, `phpcs.xml.dist`, `.phpunit.result.cache`, `.DS_Store`, `.gitattributes`, `.gitignore`, `AGENTS.md`, `CLAUDE.md`, `README.md`, `CHANGELOG.md`, `RELEASE-NOTES-*.md`, `WORDPRESS_ORG_SUBMISSION_CHECKLIST.md`). Package: 198.9 KB, 16 files.
- Repaired project memory structure: added Project Memory Metadata v1 frontmatter to all 4 `docs/*.md` memory files via the bundled `repair_metadata.py` script; deduped `PROJECT_CONTEXT.md` Development Workflow section (commands now point to `AGENTS.md`).
- Verification run: `./vendor/bin/phpcs` (clean), `php -l` on all edited PHP files (no syntax errors), `npx pressship verify .` (passed, all code-level warnings gone), `npx pressship publish . --dry-run` ("Success: Checks complete. No errors found."), `npx pressship status .` (confirmed Awaiting Review). PHPUnit could not run (no MySQL/Docker in this environment); changes were comment-only so regression risk was nil.

## 2026-05-26

- Verified the plugin against WordPress 7.0 using Docker containers for WordPress 7.0/PHP 8.3 and MySQL 8.4.
- Removed `enjinmel-smtp.php` from Composer `autoload.files` so Composer dev tools execute outside WordPress.
- Aligned test tooling to WordPress 7.0: `wp-phpunit` 7.0, PHPUnit 9.6, and PHPUnit Polyfills 4.0.
- Renamed PHPUnit tests to `*_Test.php` / `*_Test` names for reliable discovery.
- Restored legacy EngineMail compatibility helpers and metadata while keeping the current EnjinMel API payload shape.
- Hardened Send Test Email recipient validation and made table-existence checks work with both persistent and temporary tables.
- Updated `Tested up to` metadata to WordPress 7.0.
- Bumped release metadata and docs to `0.2.5` across plugin header, WordPress readme, README, changelog, POT header, docs index, and release notes.
- Verification run: Docker-backed `./vendor/bin/phpunit` against WordPress 7.0, `./vendor/bin/phpcs`, PHP syntax lint, live WP-CLI plugin activation checks, mocked `wp_mail()` success/error probes, host HTTP 200 check, and Composer audit checks.

## 2026-05-15

- Initialized project memory via `project-memory` setup.
- Created `docs/PROJECT_CONTEXT.md`, `docs/DECISIONS.md`, `docs/TASKS.md`, and `docs/CHANGELOG_WORK.md`.
- Updated/confirmed `AGENTS.md` includes the project memory maintenance requirement.
- Populated memory with verified project facts from `enjinmel-smtp.php`, `README.md`, `CHANGELOG.md`, `composer.json`, `phpcs.xml.dist`, `phpunit.xml`, `.gitattributes`, tests, specs, and recent git history.
- Fixed security scan findings:
  - `enjinmel-smtp.php` now preserves any non-null `pre_wp_mail` return value, including `WP_Error`, before sending through EnjinMel.
  - `enjinmel-smtp.php` now inserts dynamic test-email failure messages as text instead of HTML.
  - `includes/class-enjinmel-smtp-log-viewer.php` now neutralizes spreadsheet formula prefixes in CSV exports.
  - `composer.json` / `composer.lock` now use patched PHPUnit `12.5.25`.
- Added regression coverage for prior `WP_Error` preservation and CSV formula neutralization.
- Verification run: `./vendor/bin/phpunit`, filtered PHPUnit tests for both fixes, `./vendor/bin/phpcs`, PHP lint for changed PHP files, `composer audit --locked`, and `composer audit --locked --no-dev`.
- Updated `CHANGELOG.md`, `README.md`, and `readme.txt` with unreleased security-fix notes.
- Bumped release metadata and docs to `0.2.4` across plugin header, WordPress readme, README, changelog, POT header, docs index, and release notes.
- Committed and pushed the `0.2.4` security release changes to `origin/main` as `8bcc31d` (`Release 0.2.4 security fixes`).
