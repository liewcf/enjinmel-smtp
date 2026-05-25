# EnjinMel SMTP v0.2.5 Release Notes

WordPress 7.0 compatibility maintenance release for EnjinMel SMTP.

## Compatibility

- Verifies the plugin against WordPress 7.0 using a Docker-backed WordPress 7.0/PHP 8.3/MySQL test environment.
- Updates WordPress metadata to `Tested up to: 7.0`.
- Aligns the WordPress test stack with WordPress 7.0: `wp-phpunit` 7.0, PHPUnit 9.6, and PHPUnit Polyfills 4.0.

## Fixes

- Restores legacy EngineMail compatibility helpers for settings, log migration, cron cleanup, and mail failure metadata while keeping current EnjinMel behavior.
- Hardens Send Test Email recipient validation so tampered input is rejected instead of sanitized into a different address.
- Makes log-table detection work for both persistent WordPress tables and temporary tables created by the WordPress PHPUnit test suite.

## Development Tooling

- Removes the plugin bootstrap from Composer `autoload.files` so Composer dev tools can run outside WordPress.
- Renames PHPUnit test files/classes to `*_Test.php` / `*_Test` naming for reliable PHPUnit discovery.

## Verification

- Passed PHPCS, PHP syntax lint, Composer validation, Composer audit without abandoned-package failures, Docker-backed WordPress 7.0 PHPUnit, live plugin activation, mocked `wp_mail()` success/error probes, and host HTTP checks.
