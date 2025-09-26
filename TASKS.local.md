# Local Follow-Up Tasks

- [ ] Run the WordPress PHPUnit suite (e.g. `composer test` or `npx wp-env run phpunit`) to validate the REST transport and new tests.
- [ ] From wp-admin → Settings → EngineMail SMTP, toggle “Enable Logging” off/on and confirm entries stop/start in `wp_enginemail_smtp_logs`.
- [ ] Send an HTML email with attachments under and over 5 MB through the plugin to verify `SubmittedContentType`, `IsHtmlContent`, and attachment size handling against the EngineMail API.
