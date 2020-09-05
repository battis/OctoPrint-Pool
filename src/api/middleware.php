<?php


use Battis\OAuth2\Middleware\Authorization;
use DI\Container;
use OAuth2\Server as OAuth2Server;
use Psr\Container\ContainerInterface;
use Slim\App;
use Tuupola\Middleware\CorsMiddleware;

/** @var App $app */
/** @var Container $container */

$container->set(CorsMiddleware::class, function (ContainerInterface $container) {
    $settings = $container->get('settings')[CorsMiddleware::class];
    $corsOrigin = json_decode($settings['origin']);
    if (($i = array_search('@', $corsOrigin, true)) !== false) {
        $corsOrigin[$i] = 'http' . ($_SERVER['HTTPS'] ? 's' : '') . "://{$_SERVER['HTTP_HOST']}";
    }
    return new CorsMiddleware([
        'origin' => $corsOrigin,
        'headers.allow' => json_decode($settings['headers.allow']),
        'methods' => json_decode($settings['methods']),
        'cache' => $settings['cache'],
        'credentials' => true
    ]);
});

$container->set(Authorization::class, function (ContainerInterface $container) {
    return new Authorization($container->get(OAuth2Server::class));
});

$app->add(CorsMiddleware::class);
