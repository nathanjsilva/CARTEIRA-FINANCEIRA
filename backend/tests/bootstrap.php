<?php

// Override OS-level env vars (set by Docker) so PHPUnit test environment
// values win over Docker container environment and .env file values.
$testEnv = [
    'APP_ENV'          => 'testing',
    'DB_CONNECTION'    => 'sqlite',
    'DB_DATABASE'      => ':memory:',
    'CACHE_STORE'      => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER'   => 'array',
    'MAIL_MAILER'      => 'array',
    'BCRYPT_ROUNDS'    => '4',
];

foreach ($testEnv as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key]    = $value;
    $_SERVER[$key] = $value;
}

require_once __DIR__ . '/../vendor/autoload.php';
