<?php

namespace Battis\OctoPrintPool;

use Battis\OctoPrintPool\OAuth2\Actions\WeakAuthorize;
use Battis\OctoPrintPool\Queue\Actions\AnonymousEnqueueFile;
use Battis\OctoPrintPool\Queue\Actions\DequeueFile;
use Battis\OctoPrintPool\Queue\Actions\EnqueueFile;
use Battis\OctoPrintPool\Queue\Actions\ListQueue;
use Battis\OctoPrintPool\Queue\Actions\PeekFile;
use Battis\WebApp\Server\CORS\Actions\Preflight;
use Battis\WebApp\Server\OAuth2\Actions\Authorize;
use Battis\WebApp\Server\OAuth2\Middleware\Authorization;
use Chadicus\Slim\OAuth2\Routes\Token;
use DI\Container;
use OAuth2\Server;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Collector;

/** @var App $app */
/** @var Container $container */

// TODO this should probably live in settings.php or api.php
$version = basename($_SERVER['SCRIPT_FILENAME'], '.php');
$app->setBasePath($_ENV['PUBLIC_PATH'] . "/api/$version");

// TODO this is a crappy approach -- everything is a false positive
$app->options(Preflight::ROUTE, Preflight::class);

$app->group('/oauth2', function (Collector $oauth2) use ($container) {
    $oauth2->map(['GET', 'POST'], Authorize::ROUTE, Authorize::class)->setName('authorize');
    $oauth2->map(['GET', 'POST'], WeakAuthorize::ROUTE, WeakAuthorize::class)->setName('weak-authorize');
    $oauth2->post(Token::ROUTE, new Token($container->get(Server::class)))->setName('token');
});

$app->group('', function () use ($app) {
    $app->group('/queue', function (Collector $queue) {
        $queue->get('', ListQueue::class);
        $queue->post('', EnqueueFile::class);
        $queue->get('/{id}', PeekFile::class);
        $queue->delete('/{id}', DequeueFile::class);
    });
})->add(Authorization::class);

$app->group('/anonymous', function (Collector $anonymous) {
    $anonymous->post('/queue/{user_id}', AnonymousEnqueueFile::class);
});
