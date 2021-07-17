<?php

namespace Battis\OctoPrintPool;

use Monolog\Logger;
use OAuth2\Server as OAuth2Server;
use PDO;
use Tuupola\Middleware\CorsMiddleware;

return [
    'addContentLengthHeader' => false,

    CorsMiddleware::class => [
        'origin' => $_ENV['CORS_ORIGIN'] ?: 'http' . ($_SERVER['HTTPS'] ? 's' : '') . "://{$_SERVER['HTTP_HOST']}",
        'headers.allow' => $_ENV['CORS_HEADERS'] ?: '["Authorization","Accept","Content-Type"]',
        'methods' => $_ENV['CORS_METHODS'] ?: '["POST","GET","OPTIONS"]',
        'cache' => $_ENV['CORS_CACHE'] ?: 0
    ],

    PDO::class => [
        'dsn' => $_ENV['DB_DSN'] ?: 'sqlite:' . __DIR__ . '/../../var/pool.db',
        'username' => $_ENV['DB_USER'] ?: null,
        'password' => $_ENV['DB_PASSWORD'] ?: null,
    ],

    Logger::class => [
        'name' => $_ENV['APP_NAME'],
        'path' => __DIR__ . '/../../logs/server.log'
    ],

    OAuth2Server::class => [
        'access_lifetime' => $_ENV['ACCESS_TOKEN_LIFETIME_IN_MINUTES'] * 60 ?: 3600,
        'always_issue_new_refresh_token' => true,
        'refresh_token_lifetime' => $_ENV['REFRESH_TOKEN_LIFETIME_IN_MINUTES'] * 60 ?: 6 * 7 * 24 * 60 * 60,
        'www_realm' => $_ENV['WWW_REALM'] ?: $_ENV['APP_NAME']
    ]
];

