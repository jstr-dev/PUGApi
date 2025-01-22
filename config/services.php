<?php

return [
    'slapshot' => [
        'key' => getenv('SLAPSHOT_API_KEY'),
        'host' => (config('app.debug') ? 'staging' : 'api') . 'slapshot.gg'
    ]
];
