<?php


use DI\Container;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use OAuth2\GrantType;
use OAuth2\Server as OAuth2Server;
use OAuth2\Storage\Pdo as OAuth2PDOStorage;
use Psr\Container\ContainerInterface;

/** @var Container $container */

$container->set(Logger::class, function (ContainerInterface $container) {
    $settings = $container->get('settings')[Logger::class];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new WebProcessor());
    if (false === empty($settings['path'])) {
        $logger->pushHandler(new RotatingFileHandler($settings['path'], 0, Logger::DEBUG));
    } else {
        $logger->pushHandler(new ErrorLogHandler(0, Logger::DEBUG, true, true));
    }
    return $logger;
});

$container->set(PDO::class, function (ContainerInterface $container) {
    $settings = $container->get('settings')[PDO::class];

    $pdo = new PDO($settings['dsn'], $settings['username'], $settings['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if (strpos($settings['dsn'], 'sqlite') === 0) {
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    return $pdo;
});

$container->set(OAuth2Server::class, function (ContainerInterface $container) {
    $settings = $container->get('settings')[OAuth2Server::class];
    $storage = new OAuth2PDOStorage($container->get(PDO::class));

    $server = new OAuth2Server($storage, [
        'access_lifetime' => $settings['access_lifetime'],
        'www_realm' => $settings['www_realm']
    ]);
    $server->addGrantType(new GrantType\ClientCredentials($storage)); // FIXME needs to be removed or have scope
                                                                      //   limited to information about app
    $server->addGrantType(new GrantType\AuthorizationCode($storage));
    $server->addGrantType(new GrantType\RefreshToken($storage, [
        'always_issue_new_refresh_token' => $settings['always_issue_new_refresh_token']
    ]));

    return $server;
});
