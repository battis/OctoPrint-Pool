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
        'access_lifetime' => getenv('ACCESS_TOKEN_LIFETIME') ?: 3600,
        'always_issue_new_refresh_token' => true,
        'refresh_token_lifetime' => getenv('REFRESH_TOKEN_LIFETIME') ?: 6 * 7 * 24 * 60 * 60,
        'www_realm' => getenv('WWW_REALM') ?: 'OctoPrint Pool'
    ]
];

