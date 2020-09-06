<?php

use Battis\CORS\Actions\Preflight;
use Battis\OAuth2\Actions\Authorize;
use Battis\OAuth2\Middleware\Authorization;
use Battis\OctoPrintPool\Queue\Actions\AnonymousEnqueueFile;
use Battis\OctoPrintPool\Queue\Actions\DequeueFile;
use Battis\OctoPrintPool\Queue\Actions\EnqueueFile;
use Battis\OctoPrintPool\Queue\Actions\ListQueue;
use Battis\OctoPrintPool\Queue\Actions\PeekFile;
use Chadicus\Slim\OAuth2\Routes\Token;
use DI\Container;
use OAuth2\Server;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface;


/** @var App $app */
/** @var Container $container */

// TODO this should probably live in settings.php or api.php
$version = basename($_SERVER['SCRIPT_FILENAME'], '.php');
$app->setBasePath(getenv('ROOT_PATH') . "/api/$version");

// FIXME this is a crappy approach -- everything is a false positive
$app->options(Preflight::ROUTE, Preflight::class);

$app->group('/oauth2', function (RouteCollectorProxyInterface $oauth2) use ($container) {
    $oauth2->map(
        ['GET', 'POST'],
        Authorize::ROUTE,
        Authorize::class
    )->setName('authorize');
    $oauth2->post(Token::ROUTE, new Token($container->get(Server::class)))->setName('token');
});

$app->group('', function () use ($app) {
    $app->group('/queue', function (RouteCollectorProxyInterface $queue) {
        $queue->get('', ListQueue::class);
        $queue->post('', EnqueueFile::class);
        $queue->get('/{id}', PeekFile::class);
        $queue->delete('/{id}', DequeueFile::class);
    });
})->add(Authorization::class);

$app->group('/anonymous', function (RouteCollectorProxyInterface $anonymous) {
    $anonymous->post('/queue/{user_id}', AnonymousEnqueueFile::class);
});
