<?php

use DI\Container;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';

date_default_timezone_set('America/New_York');

Dotenv::create(__DIR__ . '/../../')->load();

$settings = require __DIR__ . '/settings.php';

$container = new Container();
$container->set('settings', require 'settings.php');
$app = AppFactory::createFromContainer($container);

require __DIR__ . '/dependencies.php';
require __DIR__ . '/middleware.php';
require __DIR__ . '/routes.php';

$app->addErrorMiddleware(true, false, false); // FIXME adjust for production

$app->run();
