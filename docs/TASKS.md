---
title: Current Tasks
description: Current tasks, blockers, verification state, and recommended next actions.
doc_type: task_state
status: active
created: 2026-07-03
updated: 2026-07-15
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

- [x] Keep `readme.txt`, `README.md`, plugin header version, changelog, and release notes aligned for the `0.2.5` compatibility release.
- [x] Verify WordPress 7.0 compatibility in a Docker-backed WordPress 7.0/PHP 8.3/MySQL test environment.
- [x] Resolve WordPress.org Plugin Check warnings before submitting `0.2.5`.
- [x] Submit `0.2.5` to WordPress.org for review.
- [x] Rebuild and verify `dist/enjinmel-smtp.zip` after the stale ZIP failed automated scanning.
- [x] Reupload the corrected `dist/enjinmel-smtp.zip` to WordPress.org on 2026-07-13.
- [x] Resolve the 2026-07-14 manual-review findings for inline settings scripts and external-service disclosure.
- [x] Verify the exact corrected package with PHPCS, PHPUnit, ZIP inspection, readme validation, and Plugin Check.
- [ ] Upload the 2026-07-15 corrected package and reply briefly in the existing WordPress.org review thread.

## Blockers

- Uploading the corrected package and replying to the reviewer are externally visible actions that require maintainer approval.

## Recommended Next Action

- Upload the verified `dist/enjinmel-smtp.zip`, then reply briefly in the existing review thread after WordPress.org confirms receipt.

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
