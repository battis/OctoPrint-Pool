<?php

use Battis\OctoPrintPool\PooledObject;
use Battis\OctoPrintPool\Routes\Auth;
use Battis\OctoPrintPool\Routes\Queue;
use Battis\OctoPrintPool\Routes\Users;
use Battis\RestfulAPI\Authentication\JWTOperations;
use Battis\RestfulAPI\Middleware\Application\IncludeRestfulChildren;
use Battis\RestfulAPI\RestfulAPI as API;
use Dotenv\Dotenv;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tuupola\Middleware\CorsMiddleware;
use Tuupola\Middleware\JwtAuthentication;

require_once __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('America/New_York');

Dotenv::create(__DIR__)->load();

$debugging = filter_var(getenv('DEBUGGING'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
if ($debugging) {
    error_reporting(E_ALL);
    ini_set('display_errors', 'stdout');
}

$root = getenv('ROOT_PATH');
$serverUri = "https://{$_SERVER['HTTP_HOST']}$root";
$version = basename($_SERVER['SCRIPT_FILENAME'], '.php');

// TODO convert this into a settings object that is hydratable?
$corsOrigin = json_decode(getenv('CORS_ORIGIN'));
if (($i = array_search('@', $corsOrigin, true)) !== false) {
    $corsOrigin[$i] = 'http' . ($_SERVER['HTTPS'] ? 's' : '') . "://{$_SERVER['HTTP_HOST']}";
}

// TODO Fix this hack -- necessary to extend RestfulObject to include $USER_BINDING for auth lookups
JWTOperations::bindObjectType(PooledObject::class);

API::create([

    API::_DEBUGGING => $debugging,
    API::_API_ROOT => "$root",
    API::_API_VERSION => $version,

    API::_PDO => new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USER'),
        getenv('DB_PASSWORD')
    ),
    API::_TABLE_PREFIX => getenv('DB_TABLE_PREFIX'),

    API::_DEPENDENCIES => [
        'logger' => function () {
            $logger = new Logger('api');

            $formatter = new LineFormatter(
                "[%datetime%] [%level_name%]: %message% %context%\n",
                null,
                true,
                true
            );

            // Log to timestamped files
            $rotating = new RotatingFileHandler(__DIR__ . "/logs/api.log", 0, Logger::DEBUG);
            $rotating->setFormatter($formatter);
            $logger->pushHandler($rotating);

            return $logger;
        }
    ],

    API::_MIDDLEWARE => [
        new IncludeRestfulChildren([
            IncludeRestfulChildren::_DEFAULT_INCLUDE => []
        ]),
        /*
         * TODO extend JwtAuthentication/merge with JWTOperations to create a simpler-to-use JWT authentication AND
         *  validation regime
         */
        new JwtAuthentication([
            'path' => "$root/api",
            'ignore' => ["$root/api/$version/auth", "$root/api/$version/auth/logout"],
            'cookie' => false,
            'attribute' => JWTOperations::ATTR_TOKEN,
            'secret' => getenv('JWT_SECRET'),
            'before' => function (RequestInterface $request, array $args = []) {
                if (false === empty($args['decoded'])) {
                    JWTOperations::validateTokenClaims($args['decoded']);
                }
            },
            "error" => function (ResponseInterface $response, array $args) {
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->getBody()->write(json_encode(['error' => 'unauthorized']));
            }
        ]),
        new JwtAuthentication([
            'path' => "$root/api/$version/auth/refresh",
            'ignore' => "$root/api/$version/auth/logout",
            'header' => false,
            'cookie' => JWTOperations::COOKIE_REFRESH,
            'attribute' => JWTOperations::ATTR_TOKEN,
            'secret' => getenv('JWT_SECRET'),
            'before' => function (RequestInterface $request, array $args = []) {
                if (false === empty($args['decoded'])) {
                    JWTOperations::validateTokenClaims($args['decoded']);
                }
            },
            "error" => function (ResponseInterface $response, array $args) {
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->getBody()->write(json_encode(['error' => 'unauthorized']));
            }
        ]),
        new CorsMiddleware([
            'origin' => ['http://octoprint.lvh.me:5000'],
            'headers.allow' => json_decode(getenv('CORS_HEADERS')),
            'methods' => json_decode(getenv('CORS_METHODS')),
            'cache' => getenv('CORS_CACHE')
        ])
    ],

    API::_ROUTES => [
        Auth::endpoint(),
        Users::endpoint(),
        Queue::endpoint()
    ],

    API::_QUERY_ROUTES => $debugging
]);
