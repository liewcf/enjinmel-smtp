---
title: Project Context
description: Stable project facts, structure, workflows, resources, and constraints.
doc_type: context
status: stable
created: 2026-07-03
updated: 2026-07-15
tags:
  - project-memory
  - context
  - durable-knowledge
audience:
  - agent
  - maintainer
related:
  - DECISIONS.md
  - TASKS.md
  - CHANGELOG_WORK.md
---

# Project Context

## Overview

- Project purpose: WordPress plugin that intercepts `wp_mail()` and sends mail through the Enginemailer REST API for EnjinMel SMTP.
- Primary users: WordPress site owners/admins who need transactional email delivery via Enginemailer; future maintainers preparing WordPress.org-compatible releases.
- Current status: WordPress.org received the corrected `0.2.5` package on 2026-07-13. The 2026-07-14 manual review requested proper settings-page script enqueuing and a complete Enginemailer external-service disclosure. Both fixes and their regression tests were completed on 2026-07-15; the exact replacement ZIP passes PHPCS, PHPUnit, readme validation, and Plugin Check and is ready to upload. Plugin ID: 335769; slug: `enjinmel-smtp`.

## Architecture

- Main bootstrap: `enjinmel-smtp.php`.
- Core classes live in `includes/`:
  - `EnjinMel_SMTP_API_Client` normalizes `wp_mail()` arguments and builds/sends REST payloads.
  - `EnjinMel_SMTP_Encryption` encrypts/decrypts stored API keys.
  - `EnjinMel_SMTP_Settings_Page` owns admin settings and test email AJAX flow.
  - `EnjinMel_SMTP_Logging` records mail success/failure events and manages retention.
  - `EnjinMel_SMTP_Log_Viewer` renders/searches/exports log records.
- The plugin is not namespaced. New functions use the `enjinmel_smtp_` prefix; legacy EngineMail/Enginemailer naming may appear for compatibility.
- Runtime data includes the WordPress option key `enjinmel_smtp_settings` and log table name from `enjinmel_smtp_log_table_name()`.
- Settings-page interactions are implemented in `assets/js/settings.js`, enqueued only on the EnjinMel settings screen, with AJAX configuration and translated strings supplied by WordPress.
- Public distribution is controlled by `.gitattributes` export-ignore rules so development assets are excluded from archive builds.

## Development Workflow

- Dev tooling: Composer (dev dependencies only; no runtime deps). Run commands via `vendor/bin/`.
- Coding standards: WordPress Coding Standards configured in `phpcs.xml.dist`.
- Test stack: PHPUnit `^9.6` with unit and integration suites (`phpunit.xml`); WordPress 7.0 testing uses `wp-phpunit` `^7.0` and PHPUnit Polyfills `^4.0`.
- Test bootstrap: `tests/bootstrap.php` reads `WP_TESTS_DIR`, falling back to `/tmp/wordpress-tests-lib` on macOS.
- Optional local WP environment: `@wordpress/env` (`wp-env`) is documented in the README.
- Distribution: `git archive` with `.gitattributes` `export-ignore` rules (see `AGENTS.md` for exact commands).
- Feature planning files/scripts live under `specs/`, `scripts/`, and `templates/`.
- See `AGENTS.md` for the canonical build, lint, test, and distribution commands.

## Constraints

- Target PHP compatibility is 7.4+; avoid newer PHP syntax.
- WordPress compatibility is declared as Requires at least 5.3 and Tested up to 7.0 in the plugin header and WordPress readme.
- Follow WordPress Coding Standards and the security guidance in `AGENTS.md`.
- Use WordPress APIs for HTTP, options, sanitization, escaping, admin actions, and database access where applicable.
- Do not store secrets, API keys, credentials, database dumps, or sensitive personal data in project memory.
- Preserve backward compatibility for core `wp_mail_*` hooks and any legacy `enginemail_*` identifiers that tests or upgrade paths depend on.
