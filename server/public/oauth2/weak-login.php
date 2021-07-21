<?php

use Battis\OctoPrintPool\OAuth2\Actions\WeakAuthorize;
use DI\Container;
use Dotenv\Dotenv;

// TODO#BUILD update to correct relative path
$pathToApi = __DIR__ . '/../../../OctoPrint/OctoPrint-Pool/server';

require $pathToApi . '/vendor/autoload.php';

Dotenv::createImmutable($pathToApi . '/..')->load();
date_default_timezone_set($_ENV['TIMEZONE']);

$container = new Container();
$container->set('settings', require $pathToApi . '/src/settings.php');
require $pathToApi . '/src/dependencies.php';

$pdo = $container->get(PDO::class);

$statement = $pdo->prepare("SELECT * FROM `oauth_clients` WHERE `client_id` = :client_id");
$statement->execute(['client_id' => $_GET['client_id']]);
$client = $statement->fetch();

// TODO look up scopes

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Login</title>
    <link rel="stylesheet" href="<?= glob('../assets/*.css')[0] ?>">
</head>
<body>

<div class="modal dark gray transparent">
    <div class="content shadow white">
        <div class="header">
            <h2 class="title"><?= $client['display_name'] ?></h2>
        </div>
        <div class="body">
            <form class="form" method="post" action="<?= $_ENV['PUBLIC_PATH'] ?>/api/v1/oauth2<?=
            WeakAuthorize::ROUTE?>">
                <p><?= $client['description'] ?></p>
                <div class="form-controls">
                    <?php

                    foreach ($_GET as $key => $value) {
                        echo <<<EOT
    <input type="hidden" name="{$key}" value="{$value}">
EOT;
                    }

                    ?>
                    <label for="username"><?= $_ENV['WEAK_AUTHORIZE_USERNAME_LABEL'] ?: 'username' ?></label>
                    <input id="username" type="text" name="username" autocomplete="username">
                </div>
                <div class="buttons">
                    <button class="default" type="submit" name="is_authorized" value="yes">Login</button>
                    <button type="submit" name="is_authorized" value="no">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
