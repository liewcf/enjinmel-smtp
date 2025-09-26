# Tasks: EngineMail SMTP for WordPress

**Input**: Design documents from `/specs/001-wordpress-enginemail-smtp/`

## Phase A: Core Plugin Structure & Settings
- [x] T001: Create the main plugin file `enginemail-smtp.php` and the directory structure `includes/` and `tests/`.
- [x] T002: In `enginemail-smtp.php`, implement the Settings API integration to create the settings page under "Settings" > "EngineMail SMTP".
- [x] T003: In a new file `includes/settings-page.php`, implement the form fields for SMTP credentials (host, port, encryption, username, password).
- [x] T004: In a new file `includes/class-encryption.php`, implement the encryption/decryption logic for the SMTP password using `openssl_encrypt`.
- [x] T005: [P] In a new file `tests/unit/test-encryption.php`, write unit tests for the `Encryption` class.

## Phase B: Core Email Functionality
- [x] T006: In `enginemail-smtp.php`, implement the `phpmailer_init` hook to configure PHPMailer with the saved SMTP settings.
- [x] T007: In `includes/settings-page.php`, add the fields for "From Name" and "From Email" and the "Force From" checkbox.
- [x] T008: In the `phpmailer_init` hook implementation, add the logic to force the "From" name and email if the option is enabled.
- [x] T009: In `includes/settings-page.php`, add the "Send Test Email" feature with an AJAX handler.
- [ ] T010: [P] In a new file `tests/integration/test-email-sending.php`, write integration tests for the email sending process.

## Phase C: Logging & Error Reporting
- [x] T011: In `enginemail-smtp.php`, implement the `register_activation_hook` to create the custom database table for email logging using `dbDelta()`.
- [x] T012: In a new file `includes/class-logging.php`, implement the email logging functionality to save sent and failed emails to the custom table.
- [ ] T013: In `includes/class-logging.php`, implement the log retention policy to purge old logs based on the configured retention period.
- [ ] T014: In `includes/class-logging.php`, implement the full technical debug log for error reporting.

## Phase D: Extensibility & Internationalization
- [ ] T015: [P] In `enginemail-smtp.php`, implement the `enginemail_smtp_before_send` and `enginemail_smtp_after_send` action hooks.
- [ ] T016: [P] In `includes/class-logging.php`, implement the `enginemail_smtp_log_entry` filter hook.
- [ ] T017: [P] Review all user-facing strings in the plugin and ensure they are wrapped in gettext functions like `__()` and `_e()`.

## Phase E: Quality & Finalization
- [ ] T018: [P] Run a PHPCS check over the entire codebase to ensure it adheres to WordPress Coding Standards.
- [ ] T019: [P] Write comprehensive inline documentation (PHPDoc) for all functions, classes, and hooks.

## Dependencies
- T002 depends on T001.
- T003 depends on T002.
- T004 depends on T001.
- T005 depends on T004.
- T006 depends on T002, T004.
- T008 depends on T007, T006.
- T009 depends on T006.
- T010 depends on T006.
- T012 depends on T011.
- T013 depends on T012.
- T014 depends on T012.

## Parallel Execution Example
```
# The following tasks can be run in parallel:
Task: "T005: In a new file tests/unit/test-encryption.php, write unit tests for the Encryption class."
Task: "T010: In a new file tests/integration/test-email-sending.php, write integration tests for the email sending process."
Task: "T015: In enginemail-smtp.php, implement the enginemail_smtp_before_send and enginemail_smtp_after_send action hooks."
Task: "T016: In includes/class-logging.php, implement the enginemail_smtp_log_entry filter hook."
Task: "T017: Review all user-facing strings in the plugin and ensure they are wrapped in gettext functions like __() and _e()."
Task: "T018: Run a PHPCS check over the entire codebase to ensure it adheres to WordPress Coding Standards."
Task: "T019: Write comprehensive inline documentation (PHPDoc) for all functions, classes, and hooks."
```
