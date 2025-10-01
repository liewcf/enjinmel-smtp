# Research for EnjinMel SMTP Plugin

> _Compiled prior to the EnjinMel rename; update comparative notes if vendor names change._

## 1. Testing Framework

*   **Decision**: Use PHPUnit for testing.
*   **Rationale**: PHPUnit is the de facto standard for testing in the WordPress ecosystem. The WordPress core itself uses PHPUnit for its automated tests. There are established tools and practices (e.g., `@wordpress/env`) for setting up a PHPUnit testing environment for plugins.
*   **Alternatives considered**: PestPHP. While PestPHP offers a more modern and streamlined syntax, PHPUnit is more widely adopted and has better support within the WordPress community.

## 2. Performance Goals

*   **Decision**:
    *   Plugin activation/deactivation should not add more than 100ms to page load time.
    *   The overhead of intercepting `wp_mail` should be less than 5ms (excluding the time for the SMTP connection itself).
    *   The settings page should load in under 200ms.
*   **Rationale**: These are reasonable starting points for a lightweight plugin. The main performance impact will be the network latency of the SMTP connection, which is outside the plugin's direct control. These goals focus on the plugin's own processing overhead.
*   **Alternatives considered**: No specific alternatives, as these are initial benchmarks to be refined if performance issues arise.

## 3. Scale and Scope

*   **Decision**:
    *   The plugin will be designed to handle up to 10,000 emails per day.
    *   The email log will be capped at 10,000 entries by default, with older entries being purged automatically. This will be configurable.
*   **Rationale**: This scope covers a wide range of WordPress sites, from small blogs to medium-sized e-commerce sites. The log capping is a crucial feature to prevent the database table from growing indefinitely and impacting site performance.
*   **Alternatives considered**: Unlimited logging. This was rejected due to the high risk of database performance degradation over time.

## 4. Best Practices Research

*   **`openssl_encrypt` for Password Storage**:
    *   **Finding**: This is a suitable method for encrypting data at rest. It is important to use a strong cipher (e.g., `AES-256-CBC`), a securely stored key, and a unique initialization vector (IV) for each encryption. The encryption key should be stored securely, for example, in `wp-config.php`.
*   **WordPress Settings API**:
    *   **Finding**: This is the standard and most secure way to manage plugin settings. It handles the saving, loading, and sanitization of options stored in the `wp_options` table. It also provides CSRF protection out of the box.
*   **`dbDelta` for Custom Table Creation**:
    *   **Finding**: This is the recommended WordPress function for creating and updating custom database tables. It is important to be careful with the SQL schema provided to `dbDelta` to avoid unintended data loss or errors. The function should be called within a plugin activation hook.
*   **`phpmailer_init` hook**:
    *   **Finding**: This is the correct hook to use for modifying the PHPMailer object. It provides access to the PHPMailer instance before the email is sent, allowing for the configuration of SMTP settings.
