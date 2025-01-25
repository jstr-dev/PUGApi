<?php

return [
    'slapshot' => [
        'key' => getenv('SLAPSHOT_API_KEY'),
        'host' => 'https://' . (config('app.debug') ? 'staging' : 'api') . '.slapshot.gg'
    ],
    'pug' => [
        'shared_secret' => getenv('PUG_SHARED_SECRET'),
        'timestamp_tolerance' => 1000
    ]
];
