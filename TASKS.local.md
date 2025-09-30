# Local Follow-Up Tasks

- [ ] Run the WordPress PHPUnit suite (e.g. `composer test` or `npx wp-env run phpunit`) to validate the REST transport and new tests.
- [ ] From wp-admin → Settings → EngineMail SMTP, toggle “Enable Logging” off/on and confirm entries stop/start in `wp_enginemail_smtp_logs`.
- [ ] Send an HTML email with attachments under and over 5 MB through the plugin to verify `SubmittedContentType`, `IsHtmlContent`, and attachment size handling against the EngineMail API.

## Next Engineering Tasks

- [x] Implement `enginemail_smtp_before_send` and `enginemail_smtp_after_send` hooks around the REST submission in `includes/class-api-client.php` (pass normalized args + payload and final response).
- [x] Add `enginemail_smtp_log_entry` filter in `includes/class-logging.php` before DB insert to allow modification of log data.
- [x] Design retention policy: choose days-based (default 90 days) and/or max-rows (default 10,000). Confirm choice.
- [x] Implement retention as a daily WP-Cron purge in `includes/class-logging.php` with filterable values and unschedule on deactivation.
- [x] Add deactivation hook in `enginemail-smtp.php` to unschedule any retention events.
- [ ] Run PHPCS (WPCS) over the codebase and fix violations; ensure all user-facing strings are properly internationalized.
