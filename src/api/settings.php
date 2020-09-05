<?php


use Monolog\Logger;
use OAuth2\Server as OAuth2Server;
use Tuupola\Middleware\CorsMiddleware;

return [
    'addContentLengthHeader' => false,

    CorsMiddleware::class => [
        'origin' => getenv('CORS_ORIGIN') ?: 'http' . ($_SERVER['HTTPS'] ? 's' : '') . "://{$_SERVER['HTTP_HOST']}",
        'headers.allow' => getenv('CORS_HEADERS') ?: '["Authorization","Accept","Content-Type"]',
        'methods' => getenv('CORS_METHODS') ?: '["POST","GET","OPTIONS"]',
        'cache' => getenv('CORS_CACHE') ?: 0
    ],

    PDO::class => [
        'dsn' => getenv('DB_DSN') ?: 'sqlite:' . __DIR__ . '/../../var/pool.db',
        'username' => getenv('DB_USER') ?: null,
        'password' => getenv('DB_PASSWORD') ?: null,
    ],

    Logger::class => [
        'name' => 'octoprint-pool',
        'path' => __DIR__ . '/../../logs/api.log'
    ],

    OAuth2Server::class => [
        'access_lifetime' => 518400, // seconds = 6 weeks (3600 is "normal")
        'always_issue_new_refresh_token' => true
    ]
];

