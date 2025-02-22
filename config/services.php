<?php

return [
    'slapshot' => [
        'key' => getenv('SLAPSHOT_API_KEY'),
        'host' => 'https://' . (config('app.debug') ? 'staging' : 'api') . '.slapshot.gg',
        'webhook' => config('app.url') . '/api/slapshot/webhook',
    ],

    'pug' => [
        'shared_secret' => getenv('PUG_SHARED_SECRET'),
        'timestamp_tolerance' => 100000,
        'default_elo' => 800
    ],

    'steam' => [
        'redirect' => config('app.url') . '/steam/auth/callback',
        'client_id' => null,
        'client_secret' => null,
        'api_key' => env('STEAM_API_KEY')
    ],
];
