<?php
return [
    'env' => getenv('APP_ENV') ?: 'development',
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'name' => getenv('DB_NAME') ?: 'templates_db',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'port' => getenv('DB_PORT') ?: '3306',
    ],
    'admin' => [
        'username' => getenv('ADMIN_DEFAULT_USER') ?: 'admin',
        'password' => getenv('ADMIN_DEFAULT_PASS') ?: 'changeme',
    ],
];
