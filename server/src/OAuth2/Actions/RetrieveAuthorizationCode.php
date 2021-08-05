<?php

namespace Battis\OctoPrintPool\OAuth2\Actions;

use Battis\WebApp\Server\API\Actions\AbstractAction;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class RetrieveAuthorizationCode extends AbstractAction
{

    protected function handle(ServerRequest $request, Response $response, array $args = []): ResponseInterface
    {
        error_log('-------------');
        $authorization = null;
        $state = $args['state'];
        error_log($state);
        $redirect_uri = $request->getRequestTarget();
        error_log($redirect_uri);
        $select = $this->getPDO()->prepare('
            SELECT `authorization_code`, `expires`
                FROM `oauth_authorization_codes`
            WHERE  `redirect_uri` = :redirect_uri
        ');
        if (!empty($state)) {
            do {
                $select->execute(['redirect_uri' => $redirect_uri]);
                if (connection_aborted()) {
                    return $response;
                }
                sleep(1);
            } while($select->rowCount() < 1);
            $authorization = $select->fetch();
            $authorization['state'] = $state;
        }
        return $response->withJson($authorization);
    }
}
