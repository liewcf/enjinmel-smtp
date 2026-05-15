# Tasks

## Current

- [x] Set up repo-level project memory files.
- [x] Populate memory with verified repo facts from plugin headers, README, changelog, PHPCS/PHPUnit config, tests, and packaging rules.
- [x] Run PHPCS/PHPUnit before claiming security-fix code changes are ready.
- [x] Keep `readme.txt`, `README.md`, plugin header version, changelog, and release notes aligned for the `0.2.4` security release.
- [ ] Rebuild dist packaging from the release commit before publishing `0.2.4`.

## Blockers

- None recorded.

## Done

- 2026-05-15: Project memory initialized and populated for future Codex sessions.
- 2026-05-15: Fixed security scan findings for `pre_wp_mail` `WP_Error` preservation, CSV formula neutralization, safe test-email error rendering, and patched PHPUnit dev dependency.
- 2026-05-15: Bumped release metadata and docs to `0.2.4`.
- 2026-05-15: Committed and pushed `0.2.4` security release changes to `origin/main` as `8bcc31d`.
