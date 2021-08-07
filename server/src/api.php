<?php

namespace Battis\OctoPrintPool;

use DI\Container;
use Dotenv\Dotenv;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

Dotenv::createImmutable(__DIR__ . '/../../env/')->load();
date_default_timezone_set($_ENV['TIMEZONE']);

$debugging = boolval($_ENV['DEBUGGING']);
if ($debugging) {
    $phpLog = realpath(__DIR__ . '/../../logs/php.log');
    ini_set('error_log', $phpLog);
    (new Logger('php'))->pushHandler(new RotatingFileHandler($phpLog, 1));
}

$container = new Container();
$container->set('settings', require 'settings.php');
$app = AppFactory::createFromContainer($container);

require __DIR__ . '/dependencies.php';
require __DIR__ . '/middleware.php';
require __DIR__ . '/routes.php';

// TODO#DEV adjust for production
$app->addErrorMiddleware(true, false, false);

$app->run();
