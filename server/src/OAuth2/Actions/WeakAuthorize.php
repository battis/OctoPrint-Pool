<?php


namespace Battis\OctoPrintPool\OAuth2\Actions;


use Chadicus\Slim\OAuth2\Http\RequestBridge;
use Chadicus\Slim\OAuth2\Http\ResponseBridge;
use OAuth2\Response as OAuth2Response;
use OAuth2\Server;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response as HttpResponse;
use Slim\Http\ServerRequest as HttpServerRequest;

class WeakAuthorize
{
    public const ROUTE = '/weak-authorize';

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
            return $response->withStatus(401);
        }

        // TODO this is a hack
        $user_id = null;
        $username = $request->getParsedBodyParam('username');
        if (preg_match($_ENV['WEAK_AUTHORIZE_USERNAME_PATTERN'], $username)) {
            $statement = $this->pdo->prepare("SELECT * FROM `oauth_users` WHERE `username` = :username");
            if ($statement->execute(['username' => $username])) {
                if ($user = $statement->fetch(PDO::FETCH_ASSOC)) {
                    if ($user['password'] === null) {
                        echo 'user exists';
                        $user_id = $user['username'];
                    }
                } else {
                    $statement = $this->pdo->prepare("INSERT INTO `oauth_users` SET `username` = :username");
                    if ($statement->execute(['username' => $username])) {
                        $user_id = $username;
                    }
                }
            }
        }

        $this->server->handleAuthorizeRequest(
            $oauth2Request,
            $oauth2Response,
            boolval($authorized) === true && !empty($user_id), // TODO that is, the form field 'authorized' = 'yes'
            $user_id
        );

        $bridgedResponse = ResponseBridge::fromOauth2($oauth2Response);

        $stateEndpoint = preg_replace('@^(.*/api/v\d+/oauth2)/.*@', '$1',
                $request->getRequestTarget())
            . '/state/' . $request->getParsedBodyParam('state');
        // if the oauth_redirect_uri is to the /oauth2/state/:state endpoint so a front-end client can retrieve  the
        // authorization code, don't redirect to oauth_redirect_uri, instead show the user a page thanking them for
        // authorizing the app
        if (strpos(implode($bridgedResponse->getHeader('Location')), $stateEndpoint) === 0) {
            $root = preg_replace('@^(.*)/api/v\d+/oauth2/.*@', '$1', $request->getRequestTarget());
            return $response->withRedirect("$root/authorized");
        }
        return $bridgedResponse;
    }
}
