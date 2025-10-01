# Rebrand Plan: EnjinMel SMTP (slug: enjinmel-smtp)

## Decision & Compliance

- [x] Select new name and slug: EnjinMel SMTP / enjinmel-smtp.
- [ ] Pause distribution (make repo private if public) and add a temporary README notice about the rename and lack of affiliation with EngineMail.
- [ ] Run quick name checks (USPTO TESS, EUIPO, WIPO Global Brand DB), WordPress.org plugin slugs, GitHub, and domain availability for "enjinmel-smtp".
- [ ] Add trademark disclaimer to README: "EnjinMel is not affiliated with EngineMail."

## Rename Map (no code yet)

- [ ] Function prefix: `enginemail_smtp_` → `enjinmel_smtp_`.
- [ ] Class prefix: `EngineMail_SMTP_` → `EnjinMel_SMTP_`.
- [ ] Text domain: `enginemail-smtp` → `enjinmel-smtp`.
- [ ] Constants: `ENGINEMAIL_SMTP_KEY/IV` → `ENJINMEL_SMTP_KEY/IV` (keep fallback reading old constants).
- [ ] Options: `enginemail_smtp_*` → `enjinmel_smtp_*` (copy values; keep read fallback).
- [ ] Logs table: `wp_enginemail_smtp_logs` → `wp_enjinmel_smtp_logs` (create + copy; keep read fallback if needed).
- [ ] Cron event: `enginemail_smtp_retention_daily` → `enjinmel_smtp_retention_daily` (unschedule old on deactivation).
- [ ] Plugin header/file/folder: display name → "EnjinMel SMTP", text domain → `enjinmel-smtp`, main file/folder to new slug.

## Migration Tasks (to implement next)

- [ ] Add activation routine to migrate options and create the new logs table if missing; copy data.
- [ ] Add compatibility layer to read old constants/options if new ones are absent.
- [ ] Update admin menu slug, page hooks, and settings links to the new slug.
- [ ] Update i18n loader to the new text domain and scan strings.
- [ ] Update composer.json (package name/description/autoload if class namespace changes).
- [ ] Update README, AGENTS.md, memory notes, specs, and screenshots to reflect EnjinMel.
- [ ] Tag a pre-rename commit for rollback, then open a PR with the migration plan.

## Verification Checklist (post-rename)

- [ ] Run `composer test` (or `npx wp-env run phpunit`) and ensure all tests pass.
- [ ] Start `npx wp-env start` and verify Settings → EnjinMel SMTP loads correctly.
- [ ] Toggle “Enable Logging” off/on and confirm entries stop/start in `wp_enjinmel_smtp_logs`.
- [ ] Send HTML emails with attachments under and over 5 MB; verify API fields `SubmittedContentType` and `IsHtmlContent` and attachment size handling.
- [ ] Confirm retention cron runs and deactivation unschedules the old/new events.
- [ ] Run PHPCS (WPCS) and fix violations; confirm all user-facing strings use the new text domain.

## Coordination

- [ ] Create a feature branch: `bash scripts/create-new-feature.sh "rebrand to EnjinMel SMTP"`.
- [ ] Add a `specs/NNN-rebrand-enjinmel/` folder with `spec.md`, `plan.md`, and `tasks.md` summarizing the migration.

# Local Follow-Up Tasks

- [ ] Run the WordPress PHPUnit suite (e.g. `composer test` or `npx wp-env run phpunit`) to validate the REST transport and new tests.
- [ ] From wp-admin → Settings → EnjinMel SMTP, toggle “Enable Logging” off/on and confirm entries stop/start in `wp_enjinmel_smtp_logs`.
- [ ] Send an HTML email with attachments under and over 5 MB through the plugin to verify `SubmittedContentType`, `IsHtmlContent`, and attachment size handling against the EngineMail API.

## Next Engineering Tasks

- [x] Implement `enginemail_smtp_before_send` and `enginemail_smtp_after_send` hooks around the REST submission in `includes/class-api-client.php` (pass normalized args + payload and final response).
- [x] Add `enginemail_smtp_log_entry` filter in `includes/class-logging.php` before DB insert to allow modification of log data.
- [x] Design retention policy: choose days-based (default 90 days) and/or max-rows (default 10,000). Confirm choice.
- [x] Implement retention as a daily WP-Cron purge in `includes/class-logging.php` with filterable values and unschedule on deactivation.
- [x] Add deactivation hook in `enginemail-smtp.php` to unschedule any retention events.
- [x] Run PHPCS (WPCS) over the codebase and fix violations; ensure all user-facing strings are properly internationalized.
