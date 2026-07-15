---
title: Decisions
description: Important project, product, technical, process, or content decisions with rationale and consequences.
doc_type: decision_log
status: active
created: 2026-07-03
updated: 2026-07-15
tags:
  - project-memory
  - decisions
  - rationale
audience:
  - agent
  - maintainer
related:
  - PROJECT_CONTEXT.md
  - TASKS.md
  - CHANGELOG_WORK.md
---

# Decisions

## 2026-05-15

- Initialized standard project memory files in `docs/` and added/confirmed the `AGENTS.md` project memory requirement.
- Treat the repo as a WordPress.org-oriented plugin project: future changes should prioritize WPCS, WordPress security APIs, PHP 7.4 compatibility, and backward compatibility with `wp_mail()` behavior.
- Keep release packaging based on `git archive --worktree-attributes` unless a future release process intentionally replaces it.

## 2026-05-26

- Use the WordPress-supported PHPUnit 9 line for WordPress 7.0 plugin tests. `wp-phpunit` 7.0 still targets PHPUnit 9, so the dev stack is `phpunit/phpunit:^9.6`, `wp-phpunit/wp-phpunit:^7.0`, and `yoast/phpunit-polyfills:^4.0` rather than PHPUnit 12.

## 2026-07-03

- WordPress.org submission uses `npx pressship publish` with explicit `--ignore` globs rather than `git archive` alone. Rationale: `git archive` with `.gitattributes` `export-ignore` excludes some dev files, but tracked files like `AGENTS.md`, `CLAUDE.md`, `README.md`, `templates/`, `.gitattributes`, and `.gitignore` would still be included and flagged by Plugin Check (hidden files / application files / unexpected markdown). Pressship builds the submission zip from the working tree and runs readme validation + Plugin Check before upload. No `.pressshipignore` file exists yet; the 24 ignore globs are documented in `docs/CHANGELOG_WORK.md` (2026-07-03 entry). This partially supersedes the 2026-05-15 decision to keep release packaging based on `git archive` — `git archive` remains valid for local dist builds, but WordPress.org submission uses pressship.
- Plugin Check `DirectDB.UnescapedDBParameter` and `NonceVerification.Recommended` warnings on the log viewer/logging code are false positives and are suppressed with justified `phpcs:ignore`/`phpcs:disable` annotations rather than code changes. Rationale: the sniffer cannot trace the project-local `enjinmel_smtp_sanitize_table_name()` identifier sanitizer (MySQL placeholders cannot parameterize table names), and the `$_GET` reads in `get_logs()` are read-only list-table filter params that are sanitized and `manage_options`-gated. All write actions are nonce-protected via `check_ajax_referer`.
- The `wp_mail_succeeded` and `wp_mail_failed` hooks are intentionally fired unprefixed because they are genuine WordPress core hooks mirrored by the plugin's `pre_wp_mail` short-circuit (core never fires them when `pre_wp_mail` returns non-null). Renaming would break the plugin's own logging listeners and the integration test assertions.

## 2026-07-15

- Use one static `assets/js/settings.js` file for both settings-page interactions and enqueue it only on `toplevel_page_enjinmel_smtp`. Supply the AJAX URL, action, nonce, and translated strings with `wp_localize_script()`. Rationale: this directly addresses the manual-review requirement, avoids duplicate assets, and keeps executable JavaScript out of PHP-rendered markup.
- Keep the plugin name, slug, text domain, and version at `EnjinMel SMTP`, `enjinmel-smtp`, and `0.2.5` during the pending review. Rationale: the 2026-07-14 manual review listed only script enqueuing and external-service disclosure; changing established identifiers would add unrelated compatibility and reservation work.
- Document the default Enginemailer request data precisely in `readme.txt`, including the API key, addresses, sender, subject, body, optional identifiers, and attachment contents, together with the service's official Terms of Service and Privacy Policy links.
