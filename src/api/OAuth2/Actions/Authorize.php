<?php


namespace Battis\OAuth2\Actions;


use Chadicus\Slim\OAuth2\Http\RequestBridge;
use Chadicus\Slim\OAuth2\Http\ResponseBridge;
use OAuth2\Response as OAuth2Response;
use OAuth2\Server;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response as HttpResponse;
use Slim\Http\ServerRequest as HttpServerRequest;

class Authorize
{
    const ROUTE = '/authorize';

    /** @var Server */
    private $server;

    /** @var PDO */
    private $pdo;

    public function __construct(Server $server, PDO $pdo)
    {
        $this->server = $server;
        $this->pdo = $pdo;
    }

    public function __invoke(HttpServerRequest $request, HttpResponse $response, array $args = []): ResponseInterface
    {
        $oauth2Request = RequestBridge::toOAuth2($request);
        $oauth2Response = new OAuth2Response();

        if (false === $this->server->validateAuthorizeRequest($oauth2Request, $oauth2Response)) {
            return ResponseBridge::fromOauth2($oauth2Response);
        }

        $authorized = $oauth2Request->request('authorized');
        if (empty($authorized)) {
            return $response
                ->withRedirect('../../../login?' . http_build_query($request->getParams()));
        }

        // FIXME this is a hack
        $user_id = null;
        $statement = $this->pdo->prepare("SELECT * FROM `oauth_users` WHERE `username` = :username");
        if ($statement->execute(['username' => $request->getParsedBodyParam('username')])) {
            if ($user = $statement->fetch()) {
                if (password_verify($request->getParsedBodyParam('password'), $user['password'])) {
                    $user_id = $user['username'];
                }
            }
        }

        $this->server->handleAuthorizeRequest(
            $oauth2Request,
            $oauth2Response,
            $authorized === 'yes' && false === empty($user_id), // TODO that is, the form field 'authorized' = 'yes'
            $user_id
        );

        $bridgedResponse = ResponseBridge::fromOauth2($oauth2Response);

        // is the client expecting to get the code via SSE? then they can close the page now...
        $sseAuthorizationCodeDeliveryUri = preg_replace('@^(.*/api/v\d+/oauth2)/.*@', '$1',
                $request->getRequestTarget())
            . '/state/' . $request->getParsedBodyParam('state');
        if (strpos(implode($bridgedResponse->getHeader('Location')), $sseAuthorizationCodeDeliveryUri) === 0) {
            $root = preg_replace('@^(.*)/api/v\d+/oauth2/.*@', '$1', $request->getRequestTarget());
            return $response->withRedirect("$root/authorized");
        }
        return $bridgedResponse;
    }
}
