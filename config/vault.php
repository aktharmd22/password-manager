<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SecureVault Application Configuration
    |--------------------------------------------------------------------------
    |
    | All runtime constants for the SecureVault password manager. Override
    | any value via the corresponding VAULT_* environment variable.
    |
    */

    'idle_timeout_seconds' => (int) env('VAULT_IDLE_TIMEOUT_SECONDS', 600),

    'clipboard_clear_seconds' => (int) env('VAULT_CLIPBOARD_CLEAR_SECONDS', 30),

    'password_reveal_seconds' => (int) env('VAULT_PASSWORD_REVEAL_SECONDS', 10),

    'default_password_length' => (int) env('VAULT_DEFAULT_PASSWORD_LENGTH', 20),

    'login_rate_limit' => (int) env('VAULT_LOGIN_RATE_LIMIT', 5),

    'force_https' => (bool) env('VAULT_FORCE_HTTPS', false),

    'ip_whitelist' => [
        'enabled' => (bool) env('VAULT_IP_WHITELIST_ENABLED', false),
        'allowed' => array_filter(array_map('trim', explode(',', (string) env('VAULT_IP_WHITELIST', '')))),
    ],

    'password_policy' => [
        'min_length' => 12,
        'require_lowercase' => true,
        'require_uppercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
    ],

    'generator_defaults' => [
        'length' => 20,
        'uppercase' => true,
        'lowercase' => true,
        'numbers' => true,
        'symbols' => true,
        'exclude_similar' => true,
    ],

    'recovery_codes_count' => 10,

    'pagination' => [
        'credentials_per_page' => 20,
        'audit_logs_per_page' => 50,
    ],

    'audit_actions' => [
        'created' => 'Created',
        'viewed' => 'Viewed',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
        'copied_password' => 'Copied password',
        'copied_username' => 'Copied username',
        'revealed' => 'Revealed password',
        'exported' => 'Exported',
        'imported' => 'Imported',
        'login' => 'Logged in',
        'logout' => 'Logged out',
        'failed_login' => 'Failed login',
        '2fa_enabled' => 'Enabled 2FA',
        '2fa_disabled' => 'Disabled 2FA',
        '2fa_regenerated' => 'Regenerated 2FA',
        'password_changed' => 'Master password changed',
    ],
];
