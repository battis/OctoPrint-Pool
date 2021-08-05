<?php

namespace Battis\OctoPrintPool;

use Battis\OctoPrintPool\OAuth2\Actions\RetrieveAuthorizationCode;
use Battis\OctoPrintPool\OAuth2\Actions\WeakAuthorize;
use Battis\OctoPrintPool\Queue\Actions\DequeueFile;
use Battis\OctoPrintPool\Queue\Actions\EnqueueFile;
use Battis\OctoPrintPool\Queue\Actions\FilterFiles;
use Battis\OctoPrintPool\Queue\Actions\GetFileInfo;
use Battis\OctoPrintPool\Queue\Actions\GetMyFiles;
use Battis\OctoPrintPool\Queue\Actions\GetQueue;
use Battis\OctoPrintPool\Queue\Actions\FilterQueues;
use Battis\OctoPrintPool\Queue\Actions\ActiveQueue;
use Battis\OctoPrintPool\Queue\Actions\ServerInfo;
use Battis\OctoPrintPool\Queue\Actions\UpdateFileInfo;
use Battis\OctoPrintPool\Queue\Actions\UpdateQueue;
use Battis\WebApp\Server\API\Middleware\RecursiveInclude;
use Battis\WebApp\Server\CORS\Actions\Preflight;
use Battis\WebApp\Server\OAuth2\Actions\Authorize;
use Battis\WebApp\Server\OAuth2\Middleware\Authorization;
use Battis\WebApp\Server\OAuth2\Objects\User;
use DI\Container;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Collector;

/** @var App $app */
/** @var Container $container */

// TODO this should probably live in settings.php or api.php
$version = basename($_SERVER['SCRIPT_FILENAME'], '.php');
$app->setBasePath($_ENV['PUBLIC_PATH'] . "/api/$version");

// TODO this is a crappy approach -- everything is a false positive
$app->options(Preflight::ROUTE, Preflight::class);


$app->map(['GET', 'POST'], '/oauth2' . WeakAuthorize::ROUTE, WeakAuthorize::class);
$app->get('/oauth2/state/{state}', RetrieveAuthorizationCode::class);
Authorize::registerRoutes($app, $container);

$app->group('', function () use ($app) {
    User::registerRoutes($app);
    $app->group('/queues', function (Collector $queues) {
        $queues->get('', FilterQueues::class);
        $queues->group('/{queue_id}', function (Collector $queue) {
            $queue->get('', GetQueue::class);
            $queue->put('', UpdateQueue::class);
            $queue->get('/active', ActiveQueue::class);
            $queue->group('/files', function (Collector $files) {
                $files->get('', FilterFiles::class);
                $files->post('', EnqueueFile::class);
                $files->get('/mine', GetMyFiles::class);
                $files->group('/{file_id}', function (Collector $file) {
                    $file->get('', GetFileInfo::class);
                    $file->put('', UpdateFileInfo::class);
                    $file->delete('', DequeueFile::class);
                });
            });
        });
    });
    $app->group('/files', function (Collector $files) {
        $files->put('/{file_id}', UpdateFileInfo::class);
    });
})->add(RecursiveInclude::class)->add(Authorization::class);

$app->group('/anonymous', function (Collector $anonymous) {
    $anonymous->get('/info', ServerInfo::class);
});
