# Quickstart for EngineMail SMTP Plugin Development

## 1. Prerequisites

*   A local WordPress development environment.
*   PHP 7.4 or higher.
*   Composer for managing PHP dependencies (for development).
*   Node.js and npm for JavaScript dependencies (for development).

## 2. Setup

1.  Clone the repository into your `wp-content/plugins` directory.
2.  Navigate to the plugin directory: `cd wp-content/plugins/enginemail-smtp`
3.  Install PHP dependencies: `composer install`
4.  Install JavaScript dependencies: `npm install`
5.  Activate the plugin in the WordPress admin dashboard.

## 3. Running Tests

1.  Set up your testing environment. You can use `@wordpress/env` for this.
2.  Run the PHPUnit test suite: `composer test`

## 4. Basic Usage

1.  Go to "Settings" -> "EngineMail SMTP" in the WordPress admin.
2.  Configure your EngineMail SMTP credentials.
3.  Send a test email to verify your settings.