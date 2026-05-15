# Work Changelog

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
