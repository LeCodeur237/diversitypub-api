<?php

return [
    'paths' => [
        'api/*',
        'api/documentation',
        'docs/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', env(
            'CORS_ALLOWED_ORIGINS',
            'http://localhost:5173,http://127.0.0.1:5173,http://localhost:8080,https://cameroonpolitico.cm,https://www.cameroonpolitico.cm,http://cameroonpolitico.cm,http://www.cameroonpolitico.cm,https://jeux.diversitypub.com'
        ))
    ))),

    'allowed_origins_patterns' => [
        '#^https?://([a-z0-9-]+\.)*cameroonpolitico\.cm$#i',
        '#^https?://([a-z0-9-]+\.)*diversitypub\.com$#i',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
