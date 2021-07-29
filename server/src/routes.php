<?php

namespace Battis\OctoPrintPool;

use Battis\OctoPrintPool\OAuth2\Actions\WeakAuthorize;
use Battis\OctoPrintPool\Queue\Actions\DequeueFile;
use Battis\OctoPrintPool\Queue\Actions\EnqueueFile;
use Battis\OctoPrintPool\Queue\Actions\FilterFiles;
use Battis\OctoPrintPool\Queue\Actions\GetFileInfo;
use Battis\OctoPrintPool\Queue\Actions\GetQueue;
use Battis\OctoPrintPool\Queue\Actions\FilterQueues;
use Battis\OctoPrintPool\Queue\Actions\ActiveQueue;
use Battis\OctoPrintPool\Queue\Actions\ServerInfo;
use Battis\OctoPrintPool\Queue\Actions\UpdateFileInfo;
use Battis\OctoPrintPool\Queue\Actions\UpdateQueue;
use Battis\WebApp\Server\API\Middleware\RecursiveInclude;
use Battis\WebApp\Server\CORS\Actions\Preflight;
use Battis\WebApp\Server\OAuth2\Actions\Authorize;
use Battis\WebApp\Server\OAuth2\Actions\CreateUser;
use Battis\WebApp\Server\OAuth2\Actions\DeleteUser;
use Battis\WebApp\Server\OAuth2\Actions\GetClientDescriptor;
use Battis\WebApp\Server\OAuth2\Actions\GetMe;
use Battis\WebApp\Server\OAuth2\Actions\GetUser;
use Battis\WebApp\Server\OAuth2\Actions\UpdateUser;
use Battis\WebApp\Server\OAuth2\Middleware\Authorization;
use Battis\WebApp\Server\OAuth2\Traits\Meify;
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
    $oauth2->post(Token::ROUTE, new Token($container->get(Server::class)))->setName('token');
    $oauth2->get('/client/{client_id}', GetClientDescriptor::class);

    $oauth2->map(['GET', 'POST'], WeakAuthorize::ROUTE, WeakAuthorize::class)->setName('weak-authorize');
});

$app->group('', function () use ($app) {
    $app->group('/users', function (Collector $users) {
        function userPaths(Collector $users)
        {
            $users->group('/{user_id}', function (Collector $user) {
            });
        }

        /*
         * TODO RBAC or ACL! Scopes!
        $users->post('', CreateUser::class);
        */

        $users->group('/me', function (Collector $me) {
            $me->get('', GetUser::class);
            $me->put('', UpdateUser::class);
            /*
             * TODO RBAC or ACL! Scopes!
            $me->delete('', DeleteUser::class);
            */
        });

        $users->group('/{user_id}', function (Collector  $user) {
            $user->get('', GetUser::class);
            /*
             * TODO RBAC or ACL! Scopes!
            $user->put('', UpdateUser::class);
            $user->delete('', DeleteUser::class);
            */
        });
    });
    $app->group('/queues', function (Collector $queues) {
        $queues->get('', FilterQueues::class);
        $queues->group('/{queue_id}', function (Collector $queue) {
            $queue->get('', GetQueue::class);
            $queue->put('', UpdateQueue::class);
            $queue->get('/active', ActiveQueue::class);
            $queue->group('/files', function (Collector $files) {
                $files->get('', FilterFiles::class);
                $files->post('', EnqueueFile::class);
                $files->group('/{file_id}', function (Collector $file) {
                    $file->get('', GetFileInfo::class);
                    $file->put('', UpdateFileInfo::class);
                    $file->delete('', DequeueFile::class);
                });
            });
        });
    });
})->add(RecursiveInclude::class)->add(Authorization::class);

$app->group('/anonymous', function (Collector $anonymous) {
    $anonymous->get('/info', ServerInfo::class);
});
