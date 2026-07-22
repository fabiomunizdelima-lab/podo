<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configurazione specifica del gestionale Podo
    |--------------------------------------------------------------------------
    */

    'force_https' => (bool) env('FORCE_HTTPS', true),

    'security' => [
        'mfa_required_for_admins' => (bool) env('MFA_REQUIRED_FOR_ADMINS', true),
        'login_rate_limit'        => (int) env('LOGIN_RATE_LIMIT', 5),
        'password_min_length'     => (int) env('PASSWORD_MIN_LENGTH', 12),
    ],

    'whatsapp' => [
        'enabled'              => (bool) env('WHATSAPP_ENABLED', false),
        'api_version'          => env('WHATSAPP_API_VERSION', 'v21.0'),
        'phone_number_id'      => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token'         => env('WHATSAPP_ACCESS_TOKEN'),
        'template_name'        => env('WHATSAPP_TEMPLATE_NAME', 'promemoria_appuntamento'),
        'template_lang'        => env('WHATSAPP_TEMPLATE_LANG', 'it'),
        'reminder_hours_before' => (int) env('WHATSAPP_REMINDER_HOURS_BEFORE', 24),
    ],

    'google_calendar' => [
        'enabled'       => (bool) env('GOOGLE_CALENDAR_ENABLED', false),
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri'  => env('GOOGLE_REDIRECT_URI'),
        'calendar_id'   => env('GOOGLE_CALENDAR_ID', 'primary'),
    ],

];
