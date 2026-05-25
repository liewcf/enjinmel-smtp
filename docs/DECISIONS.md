# Decisions

## 2026-05-15

- Initialized standard project memory files in `docs/` and added/confirmed the `AGENTS.md` project memory requirement.
- Treat the repo as a WordPress.org-oriented plugin project: future changes should prioritize WPCS, WordPress security APIs, PHP 7.4 compatibility, and backward compatibility with `wp_mail()` behavior.
- Keep release packaging based on `git archive --worktree-attributes` unless a future release process intentionally replaces it.

## 2026-05-26

- Use the WordPress-supported PHPUnit 9 line for WordPress 7.0 plugin tests. `wp-phpunit` 7.0 still targets PHPUnit 9, so the dev stack is `phpunit/phpunit:^9.6`, `wp-phpunit/wp-phpunit:^7.0`, and `yoast/phpunit-polyfills:^4.0` rather than PHPUnit 12.
