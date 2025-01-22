<?php

return [
    'slapshot' => [
        'key' => getenv('SLAPSHOT_API_KEY'),
        'host' => 'https://' . (config('app.debug') ? 'staging' : 'api') . '.slapshot.gg'
    ]
];
