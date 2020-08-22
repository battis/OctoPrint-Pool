<?php


namespace Battis\OctoPrintPool\Routes;


use Battis\OctoPrintPool\User;
use Battis\PersistentObject\Parts\Condition;
use Battis\RestfulAPI\Authentication\JWTOperations;
use Battis\RestfulAPI\Routing\RestfulEndpoint;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;

class Auth extends RestfulEndpoint
{
    public function __construct($parent)
    {
        parent::__construct('auth', $parent);
    }

    public function defineMethods()
    {
        $this->post(
            '[/]',
            function (Request $request, Response $response) {
                $data = $request->getParsedBody();
                $users = User::getInstances(Condition::fromPairedValues(['username' => $data[User::canonical
                (User::USERNAME)]]));
                if (false === empty($users) && $user = $users[0]) {
                    if ($user->verifyPassword($data[User::canonical(User::PASSWORD)])) {
                        return $response->withJson(JWTOperations::getApiToken($user));
                    }
                }
                return $response->withStatus(401);
            },
            'login'
        );
        $this->post(
            '/refresh[/]',
            function (Request $request, Response $response) {
                $token = $request->getAttribute(JWTOperations::ATTR_TOKEN);
                if (false === empty($token[JWTOperations::CLAIM_USER_ID]) && $result = JWTOperations::refreshApiToken(
                    User::getInstanceById($token[JWTOperations::CLAIM_USER_ID]),
                    $token
                )) {
                    return $response->withJson($result);
                }
                return $response->withStatus(401);
            },
            'refresh-token'
        );
        $this->post(
            '/logout[/]',
            function(Request $request, Response $response) {
                JWTOperations::logout();
                return $response->withJson(null);
            },
            'logout'
        );
    }
}
