<?php

$origins = array_values(array_unique(array_filter(array_map('trim', explode(',', env('FRONTEND_URL', 'http://localhost:5173,http://127.0.0.1:5173'))))));

return [
    'paths'                    => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => $origins,
    'allowed_origins_patterns' => [
        '/^http:\/\/localhost(:\d+)?$/',
        '/^http:\/\/127\.0\.0\.1(:\d+)?$/',
        '/^http:\/\/0\.0\.0\.0(:\d+)?$/',
    ],
    'allowed_headers'          => ['Content-Type', 'X-Requested-With', 'X-CSRF-TOKEN', 'Authorization', 'Accept', 'Origin'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => true,
];
