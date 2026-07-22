<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    | Checklist sicurezza: le password devono usare Argon2id.
    */

    'driver' => 'argon2id',

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
    ],

    'argon' => [
        // Parametri robusti per Argon2id (OWASP: memoria >= 19 MiB)
        'memory' => (int) env('ARGON_MEMORY', 65536),   // 64 MiB
        'threads' => (int) env('ARGON_THREADS', 4),
        'time' => (int) env('ARGON_TIME', 4),
        'verify' => true,
    ],

    'rehash_on_login' => true,

];
