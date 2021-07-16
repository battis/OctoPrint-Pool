<?php


namespace Battis\OAuth2\Actions;


use PDO;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

/**
 * Class RetrieveAuthorizationCode
 * @package Battis\OAuth2\Actions
 *
 * This is, clearly, a hack.
 *
 * FIXME restrict by requesting host?
 * FIXME make this single shot -- authorization code can only be requested once?
 */
class RetrieveAuthorizationCode
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(ServerRequest $request, Response $response, array $args = [])
    {
        $authorization = null;
        $state = $args['state'];
        $redirect_uri = $request->getRequestTarget();
        $select = $this->pdo->prepare("
            SELECT `authorization_code`, `expires` FROM `oauth_authorization_codes`
                WHERE
                    `redirect_uri` = :redirect_uri
        ");
        if (!empty($state)) {
            do {
                $select->execute(['redirect_uri' => $redirect_uri]);
                // TODO do I actually care if the connection was aborted?
                if (connection_aborted()) {
                    return $response;
                }
                sleep(1);
            } while ($select->rowCount() < 1);
            $authorization = $select->fetch();
            $authorization['state'] = $state;
        }
        return $response->withJson($authorization);
    }
}
