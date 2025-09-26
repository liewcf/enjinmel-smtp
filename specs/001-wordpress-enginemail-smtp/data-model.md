# Data Model for EngineMail SMTP Plugin

## 1. Plugin Settings (`wp_options`)

The plugin settings will be stored in the `wp_options` table using the WordPress Settings API. A single array option named `enginemail_smtp_settings` will be used to store all settings.

*   **Option Name**: `enginemail_smtp_settings`
*   **Data Structure**:
    ```php
    [
        'smtp_host' => (string) 'smtp.enginemail.com',
        'smtp_port' => (int) 587,
        'smtp_encryption' => (string) 'tls', // 'none', 'ssl', 'tls'
        'smtp_username' => (string) 'your-username',
        'smtp_password' => (string) 'encrypted-password', // Encrypted
        'from_name' => (string) 'Your Site Name',
        'from_email' => (string) 'you@your-site.com',
        'force_from' => (bool) true,
        'enable_logging' => (bool) true,
        'log_retention_days' => (int) 30 // Corresponds to ~10,000 entries
    ]
    ```

## 2. Email Log (Custom Table)

A custom database table named `wp_enginemail_smtp_logs` will be created to store email logs.

*   **Table Name**: `wp_enginemail_smtp_logs`
*   **Schema**:
    ```sql
    CREATE TABLE wp_enginemail_smtp_logs (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        timestamp DATETIME NOT NULL,
        to_email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        status VARCHAR(20) NOT NULL, -- 'sent', 'failed'
        error_message TEXT,
        PRIMARY KEY (id),
        KEY timestamp (timestamp)
    )
    ```