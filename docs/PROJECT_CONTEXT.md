# Project Context

## Overview

- Project purpose: WordPress plugin that intercepts `wp_mail()` and sends mail through the Enginemailer REST API for EnjinMel SMTP.
- Primary users: WordPress site owners/admins who need transactional email delivery via Enginemailer; future maintainers preparing WordPress.org-compatible releases.
- Current status: Version `0.2.4` in plugin headers, readme metadata, changelog, and release notes for the security maintenance release.

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
- Public distribution is controlled by `.gitattributes` export-ignore rules so development assets are excluded from archive builds.

## Development Workflow

- Package manager: Composer for PHP dev tooling.
- Install dependencies: `composer install`.
- Coding standards: `./vendor/bin/phpcs` using `phpcs.xml.dist` with WordPress Coding Standards.
- Auto-fix standards: `./vendor/bin/phpcbf`.
- Tests: `./vendor/bin/phpunit`, with unit and integration suites defined in `phpunit.xml`.
- WordPress test library: `tests/bootstrap.php` reads `WP_TESTS_DIR`, falling back to `/tmp/wordpress-tests-lib` on macOS.
- Optional local WP environment: README documents `npx wp-env start` and `npx wp-env run tests-cli phpunit`.
- Distribution ZIP: `mkdir -p dist` then `git archive --format=zip --output dist/enjinmel-smtp.zip --worktree-attributes HEAD`.
- Feature planning files/scripts exist under `specs/`, `scripts/`, and `templates/`.

## Constraints

- Target PHP compatibility is 7.4+; avoid newer PHP syntax.
- WordPress compatibility is declared as Requires at least 5.3 and Tested up to 6.8 in the plugin header.
- Follow WordPress Coding Standards and the security guidance in `AGENTS.md`.
- Use WordPress APIs for HTTP, options, sanitization, escaping, admin actions, and database access where applicable.
- Do not store secrets, API keys, credentials, database dumps, or sensitive personal data in project memory.
- Preserve backward compatibility for core `wp_mail_*` hooks and any legacy `enginemail_*` identifiers that tests or upgrade paths depend on.
