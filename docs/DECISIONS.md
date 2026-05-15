# Decisions

## 2026-05-15

- Initialized standard project memory files in `docs/` and added/confirmed the `AGENTS.md` project memory requirement.
- Treat the repo as a WordPress.org-oriented plugin project: future changes should prioritize WPCS, WordPress security APIs, PHP 7.4 compatibility, and backward compatibility with `wp_mail()` behavior.
- Keep release packaging based on `git archive --worktree-attributes` unless a future release process intentionally replaces it.
