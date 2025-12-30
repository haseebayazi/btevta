<?php

/**
 * Password Policy Configuration
 *
 * Government-grade password requirements following:
 * - NIST SP 800-63B Guidelines
 * - Pakistan Government IT Security Standards
 * - OWASP Password Guidelines
 *
 * All settings can be overridden via environment variables.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Minimum Password Length
    |--------------------------------------------------------------------------
    |
    | The minimum number of characters required for passwords.
    | Government standard recommends 12+ characters.
    |
    */
    'min_length' => (int) env('PASSWORD_MIN_LENGTH', 12),

    /*
    |--------------------------------------------------------------------------
    | Character Requirements
    |--------------------------------------------------------------------------
    |
    | Define which character types are required in passwords.
    | All are enabled by default for government compliance.
    |
    */
    'require_uppercase' => (bool) env('PASSWORD_REQUIRE_UPPERCASE', true),
    'require_lowercase' => (bool) env('PASSWORD_REQUIRE_LOWERCASE', true),
    'require_number' => (bool) env('PASSWORD_REQUIRE_NUMBER', true),
    'require_special' => (bool) env('PASSWORD_REQUIRE_SPECIAL', true),

    /*
    |--------------------------------------------------------------------------
    | Special Characters Allowed
    |--------------------------------------------------------------------------
    |
    | The set of special characters that are accepted.
    |
    */
    'special_characters' => '!@#$%^&*()_+-=[]{};\':"|,.<>/?~`',

    /*
    |--------------------------------------------------------------------------
    | Password History
    |--------------------------------------------------------------------------
    |
    | Number of previous passwords to remember and prevent reuse.
    | Set to 0 to disable password history checking.
    |
    */
    'history_count' => (int) env('PASSWORD_HISTORY_COUNT', 5),

    /*
    |--------------------------------------------------------------------------
    | Password Expiry
    |--------------------------------------------------------------------------
    |
    | Number of days before a password expires and must be changed.
    | Set to 0 to disable password expiry.
    | Government standard: 90 days for privileged accounts.
    |
    */
    'expiry_days' => (int) env('PASSWORD_EXPIRY_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Expiry Warning Days
    |--------------------------------------------------------------------------
    |
    | Number of days before expiry to start warning the user.
    |
    */
    'expiry_warning_days' => (int) env('PASSWORD_EXPIRY_WARNING_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Common Passwords Check
    |--------------------------------------------------------------------------
    |
    | Whether to check passwords against a list of commonly used passwords.
    |
    */
    'check_common_passwords' => (bool) env('PASSWORD_CHECK_COMMON', true),

    /*
    |--------------------------------------------------------------------------
    | Compromised Password Check
    |--------------------------------------------------------------------------
    |
    | Whether to check passwords against known data breaches (Have I Been Pwned).
    | Requires external API call - may impact performance.
    |
    */
    'check_compromised' => (bool) env('PASSWORD_CHECK_COMPROMISED', false),

    /*
    |--------------------------------------------------------------------------
    | Role-Based Expiry
    |--------------------------------------------------------------------------
    |
    | Different expiry periods for different roles.
    | More privileged roles should have shorter expiry periods.
    |
    */
    'role_expiry_days' => [
        'super_admin' => (int) env('PASSWORD_EXPIRY_SUPER_ADMIN', 60),
        'admin' => (int) env('PASSWORD_EXPIRY_ADMIN', 60),
        'project_director' => (int) env('PASSWORD_EXPIRY_DIRECTOR', 90),
        'campus_admin' => (int) env('PASSWORD_EXPIRY_CAMPUS_ADMIN', 90),
        'trainer' => (int) env('PASSWORD_EXPIRY_TRAINER', 120),
        'oep' => (int) env('PASSWORD_EXPIRY_OEP', 120),
        'visa_partner' => (int) env('PASSWORD_EXPIRY_VISA_PARTNER', 120),
        'viewer' => (int) env('PASSWORD_EXPIRY_VIEWER', 180),
        'staff' => (int) env('PASSWORD_EXPIRY_STAFF', 120),
        'candidate' => (int) env('PASSWORD_EXPIRY_CANDIDATE', 180),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Messages
    |--------------------------------------------------------------------------
    |
    | Custom validation messages for password requirements.
    |
    */
    'messages' => [
        'min_length' => 'The password must be at least :min characters.',
        'uppercase' => 'The password must contain at least one uppercase letter.',
        'lowercase' => 'The password must contain at least one lowercase letter.',
        'number' => 'The password must contain at least one number.',
        'special' => 'The password must contain at least one special character (:chars).',
        'history' => 'You cannot reuse any of your last :count passwords.',
        'common' => 'This password is too common. Please choose a more unique password.',
        'compromised' => 'This password has been found in a data breach. Please choose a different password.',
    ],

];
