<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kerberos Authentication Enabled
    |--------------------------------------------------------------------------
    |
    | When enabled, the system will attempt to authenticate users via the
    | REMOTE_USER server variable before falling back to standard login.
    |
    */

    'enabled' => env('KERBEROS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Server Variable Name
    |--------------------------------------------------------------------------
    |
    | The server variable containing the Kerberos principal.
    | Default is REMOTE_USER for Apache/Nginx Kerberos modules.
    |
    */

    'server_variable' => env('KERBEROS_SERVER_VAR', 'REMOTE_USER'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Authentication
    |--------------------------------------------------------------------------
    |
    | When enabled, users can still authenticate via standard login/password
    | if Kerberos authentication fails or is unavailable.
    |
    */

    'fallback_auth' => env('KERBEROS_FALLBACK_AUTH', true),

    /*
    |--------------------------------------------------------------------------
    | Simulation Mode (Development Only)
    |--------------------------------------------------------------------------
    |
    | Enables Kerberos simulation mode for local/staging environments.
    | This allows developers to test Kerberos flows without a real KDC.
    |
    | WARNING: Automatically disabled if APP_ENV=production.
    |
    */

    'simulation_mode' => env('KERBEROS_SIMULATION_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Admin Notification Emails
    |--------------------------------------------------------------------------
    |
    | Comma-separated email addresses to notify for Kerberos-related events
    | (unknown user attempts, new access requests, etc.).
    |
    | If empty, the system will notify all users with the Admin role.
    |
    */

    'admin_notification_emails' => array_filter(
        explode(',', env('KERBEROS_ADMIN_EMAILS', ''))
    ),

    /*
    |--------------------------------------------------------------------------
    | Admin Notification Mode
    |--------------------------------------------------------------------------
    |
    | Controls how admins are notified about Kerberos events:
    | - 'immediate': Send email immediately for each event
    | - 'disabled': No notifications
    |
    */

    'admin_notification_mode' => env('KERBEROS_ADMIN_NOTIFICATION_MODE', 'immediate'),

    /*
    |--------------------------------------------------------------------------
    | Automatic Cleanup Days
    |--------------------------------------------------------------------------
    |
    | Number of days to retain Kerberos login attempts in the database.
    | Older attempts are automatically purged by the scheduled command.
    |
    */

    'auto_cleanup_attempts_days' => env('KERBEROS_AUTO_CLEANUP_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Allowed Domains (Optional)
    |--------------------------------------------------------------------------
    |
    | Whitelist of allowed Kerberos domains. If empty, all domains are accepted.
    | Example: ['example.fr', 'corp.example.fr']
    |
    */

    'allowed_domains' => array_filter(
        explode(',', env('KERBEROS_ALLOWED_DOMAINS', ''))
    ),

];
