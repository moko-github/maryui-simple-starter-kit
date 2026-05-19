<?php

return [
    'url'            => env('API_SI_URL'),
    'token'          => env('API_SI_TOKEN'),
    'timeout'        => (int) env('API_SI_TIMEOUT', 10),
    'scramble_url'   => env('API_SI_SCRAMBLE_URL'),
    'webhook_secret' => env('API_SI_WEBHOOK_SECRET'),
];
