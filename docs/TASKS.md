---
title: Current Tasks
description: Current tasks, blockers, verification state, and recommended next actions.
doc_type: task_state
status: active
created: 2026-07-03
updated: 2026-08-19
tags:
  - project-memory
  - tasks
  - current-state
audience:
  - agent
  - maintainer
related:
  - PROJECT_CONTEXT.md
  - DECISIONS.md
  - CHANGELOG_WORK.md
---

# Tasks

## Current

- [x] Verify EnjinMel SMTP `0.2.5` compatibility with WordPress 7.1 (RC4, 2026-08-19; no code changes needed).
- [x] Declare the `$admin_user` property in `tests/unit/AJAX_Handler_Test.php` to silence the PHP 8.2+ dynamic-property deprecation; re-verified the full suite against WordPress 7.1-RC4 with zero risky tests.
- [ ] Commit the staged WordPress.org banner and PNG icon fallback assets, then verify the public plugin page after CDN propagation.
- [ ] After WordPress 7.1 final ships and stabilizes, bump `Tested up to` to 7.1 in `readme.txt`, the plugin header, and README.md, then decide whether to release as `0.2.6` or fold into the next release.

## Blockers

- Publishing the remaining visual assets is an externally visible SVN action that requires maintainer approval.

## Recommended Next Action

- After WordPress 7.1 final ships (scheduled 2026-08-19), bump `Tested up to` to 7.1 in `readme.txt`, the plugin header, and README.md. The 7.1-RC4 verification (2026-08-19) required no code changes, so the bump is metadata-only unless the 7.1 final differs from RC4.

## Done

- 2026-05-15: Project memory initialized and populated for future Codex sessions.
- 2026-05-15: Fixed security scan findings for `pre_wp_mail` `WP_Error` preservation, CSV formula neutralization, safe test-email error rendering, and patched PHPUnit dev dependency.
- 2026-05-15: Bumped release metadata and docs to `0.2.4`.
- 2026-05-15: Committed and pushed `0.2.4` security release changes to `origin/main` as `8bcc31d`.
- 2026-05-26: Verified WordPress 7.0 compatibility, fixed test/tooling compatibility, restored legacy compatibility shims, and updated `Tested up to` metadata.
- 2026-05-26: Bumped release metadata and docs to `0.2.5`.
- 2026-07-03: Resolved 27 Plugin Check warnings (comment-only fixes + readme trim) in commit `3d49872`, pushed to `origin/main`.
- 2026-07-03: Submitted `0.2.5` to WordPress.org (Plugin ID 335769, slug `enjinmel-smtp`, Awaiting Review).
- 2026-07-03: Repaired project memory structure — added Project Memory Metadata v1 frontmatter to all `docs/*.md` files and deduped `PROJECT_CONTEXT.md`.
- 2026-07-13: Confirmed the rejected upload was the stale `dist/enjinmel-smtp.zip`; rebuilt it from commit `3d49872` and verified the exact extracted package with Pressship/Plugin Check with no plugin errors.
- 2026-07-15: Replaced both settings-page inline scripts with one conditionally enqueued admin asset, documented the Enginemailer service and transmitted data, added admin-asset regression tests, and verified the exact replacement package.
- 2026-07-16: WordPress.org published EnjinMel SMTP `0.2.5`; committed the Plugin Directory SVG icon and both screenshots in SVN revision `3609733`.
- 2026-08-19: Verified `0.2.5` against WordPress 7.1-RC4 in Docker (PHP 8.3/MySQL 8.4): full PHPUnit suite green (51 tests, 164 assertions), live-site smoke tests green, PHPCS clean; no code changes required.
- 2026-08-19: Fixed the test-only PHP 8.2+ dynamic-property deprecation in `tests/unit/AJAX_Handler_Test.php`; full suite re-run against 7.1-RC4 now reports zero risky tests.
