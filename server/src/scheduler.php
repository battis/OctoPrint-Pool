<?php

use Battis\OctoPrintPool\Queue\Objects\Queue;
use Battis\OctoPrintPool\Queue\Strategies\Cleanup\AbstractCleanupStrategy;
use DI\Container;
use Dotenv\Dotenv;
use GO\Scheduler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

require_once __DIR__ . '/../vendor/autoload.php';

Dotenv::createImmutable(__DIR__ . '/../../env/')->load();
date_default_timezone_set($_ENV['TIMEZONE']);

$debugging = boolval($_ENV['DEBUGGING']);
if ($debugging) {
    ini_set('error_log', realpath(__DIR__ . '/../../logs/php.log'));
}

// inject server settings and dependencies
$container = new Container();
$container->set('settings', require 'settings.php');
require_once __DIR__ . '/dependencies.php';

$queues = Queue::getByFilter([], null, $container->get(PDO::class), true);

$scheduler = new Scheduler();
$logger = new Logger('scheduler');
$logger->pushHandler(new RotatingFileHandler(__DIR__ . '/../../logs/cleanup.log'));

foreach ($queues as $queue) {

    if ($strategy = $queue->getCleanupStrategy()) {
        $params = $queue->getCleanupParams();
        if ($params['cron']) {
            $scheduler->call(function () use ($logger, $container, $strategy, $queue, $params) {
                /** @var AbstractCleanupStrategy $strategy */
                $strategy = new $strategy();
                $strategy($queue, $params, $logger);
                return true;
            })->at($params['cron']);
        }
    }
}

$scheduler->run();
