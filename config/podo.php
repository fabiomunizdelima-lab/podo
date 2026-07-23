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

    /*
    | Dati del prestatore (cedente) e parametri di fatturazione.
    | Vanno compilati in .env con i dati reali dello studio prima di emettere.
    | Il modello e flessibile: copre regime forfettario ed ordinario/esente.
    */
    'billing' => [
        'studio_name'     => env('BILLING_STUDIO_NAME', 'Studio Podologico'),
        'vat_number'      => env('BILLING_VAT_NUMBER', ''),
        'fiscal_code'     => env('BILLING_FISCAL_CODE', ''),
        // forfettario | ordinario
        'regime'          => env('BILLING_REGIME', 'forfettario'),
        // RF19 = forfettario, RF01 = ordinario (codice RegimeFiscale SDI)
        'tax_regime_code' => env('BILLING_TAX_REGIME_CODE', 'RF19'),
        'address'         => env('BILLING_ADDRESS', ''),
        'city'            => env('BILLING_CITY', ''),
        'cap'             => env('BILLING_CAP', ''),
        'province'        => env('BILLING_PROVINCE', ''),
        'country'         => env('BILLING_COUNTRY', 'IT'),
        'pec'             => env('BILLING_PEC', ''),
        'sdi_code'        => env('BILLING_SDI_CODE', '0000000'),
        // Nota di iscrizione albo / esenzione da riportare in fattura
        'register_note'   => env('BILLING_REGISTER_NOTE', 'Prestazione sanitaria esente IVA art.10 c.1 n.18 DPR 633/72'),
        'vat_nature'      => env('BILLING_VAT_NATURE', 'N4'),
        'currency'        => env('BILLING_CURRENCY', 'EUR'),
        // Marca da bollo (2 euro sopra soglia per operazioni non soggette/esenti)
        'stamp_threshold' => (float) env('BILLING_STAMP_THRESHOLD', 77.47),
        'stamp_amount'    => (float) env('BILLING_STAMP_AMOUNT', 2.00),
        // Ritenuta d acconto (di norma NON per forfettari)
        'withholding_enabled' => (bool) env('BILLING_WITHHOLDING_ENABLED', false),
        'withholding_rate'    => (float) env('BILLING_WITHHOLDING_RATE', 20.0),
        // Sistema Tessera Sanitaria
        'ts_enabled'      => (bool) env('BILLING_TS_ENABLED', false),
        'ts_default_type' => env('BILLING_TS_DEFAULT_TYPE', 'SR'),
    ],

];
