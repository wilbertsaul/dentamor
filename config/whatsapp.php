<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp (WAHA - devlike.pro)
    |--------------------------------------------------------------------------
    |
    | El servidor WAHA corre en el mismo host que Laravel (Docker) y solo
    | escucha en 127.0.0.1:3000. La API key se genera en el compose.
    | El número de WhatsApp se escanea una sola vez por QR desde la sesión.
    |
    */

    'base_url' => env('WHATSAPP_BASE_URL', 'http://127.0.0.1:3000'),

    'api_key' => env('WHATSAPP_API_KEY'),

    'timeout' => (int) env('WHATSAPP_TIMEOUT', 20),

    'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '51'),

];
