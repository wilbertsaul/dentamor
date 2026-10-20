<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Cloud API (Meta)
    |--------------------------------------------------------------------------
    |
    | Las credenciales reales se guardan por empresa en la tabla companies.
    | Este archivo define valores por defecto y el endpoint de la Graph API.
    |
    */

    'graph_url' => env('WHATSAPP_GRAPH_URL', 'https://graph.facebook.com'),

    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    'timeout' => (int) env('WHATSAPP_TIMEOUT', 20),

    'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '51'),

    'template_lang' => env('WHATSAPP_TEMPLATE_LANG', 'es'),

    'link_days' => (int) env('WHATSAPP_LINK_DAYS', 30),

];
