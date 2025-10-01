# Repository Guidelines

## Project Structure & Module Organization
Core plugin logic lives in `enjinmel-smtp.php`, which boots the plugin, registers admin menus, and wires settings. Place reusable PHP modules under `includes/` (for example, `includes/class-enjinmel-smtp-encryption.php` and `includes/class-enjinmel-smtp-settings-page.php`). Specs for ongoing work sit in `specs/NNN-name/` with `spec.md`, `plan.md`, and `tasks.md`. Contributor scripts reside in `scripts/`, while reusable templates live in `templates/`. WordPress PHPUnit tests belong in `tests/unit/` and should mirror the behavior under test. Project governance notes are kept in `memory/` for quick context sharing.

## Build, Test, and Development Commands
Use `bash scripts/create-new-feature.sh "describe feature"` to scaffold a numbered feature branch with starter docs. Run `bash scripts/get-feature-paths.sh` to surface the active spec folders. Execute `php -l includes/class-encryption.php` for a quick syntax check on individual PHP files. Start the local WordPress environment with `npx wp-env start`, and run the PHPUnit suite via `composer test` (or `npx wp-env run phpunit` if Composer is unavailable).

## Coding Style & Naming Conventions
Target PHP 7.4+ and follow WordPress Coding Standards. Indent with 4 spaces, UTF-8 encoding, and LF endings. Prefix functions as `enjinmel_smtp_{verb_noun}` (legacy `enginemail_smtp_` aliases remain for compatibility) and classes as `EnjinMel_SMTP_{Name}`; new PHP files should use the `class-enjinmel-smtp-*.php` pattern. Escape on output (`esc_html`, `esc_attr`, `wp_kses`) and sanitize on input. Never hardcode secrets—access keys and IVs through `ENJINMEL_SMTP_KEY`/`ENJINMEL_SMTP_IV` constants in `wp-config.php` (legacy `ENGINEMAIL_SMTP_*` constants are still accepted).

## Testing Guidelines
Write unit tests with `WP_UnitTestCase` in `tests/unit/test-*.php`, keeping one primary behavior per test method. Ensure encryption helpers maintain round-trip coverage similar to `tests/unit/test-encryption.php`. Execute `composer test` before submitting changes and note any skipped or failing tests in your PR description.

## Commit & Pull Request Guidelines
Branch names follow the `NNN-short-summary` convention (for example, `001-wordpress-enjinmel-smtp`). Commits should use Conventional Commit prefixes such as `feat:`, `fix:`, or `test:` and stay focused. PRs must summarize the change, link the relevant `specs/NNN-*` folder, include a test plan with command outputs, and attach screenshots for admin UI updates. Keep patches scoped to the feature and update specs if the plan shifts.

## Security & Configuration Tips
Restrict SMTP option access by checking capabilities and adding nonces to admin forms. Validate host, port, and encryption settings, defaulting to safe values. Store credentials in WordPress options and keep the encryption key material outside the repository.
