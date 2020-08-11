<?php


namespace Battis\OctoPrintPool\Routes;


use Battis\OctoPrintPool\User;
use Battis\RestfulAPI\Authentication\JWTOperations;
use Battis\RestfulAPI\Middleware\Application\IncludeRestfulChildren;
use Battis\RestfulAPI\Routing\RestfulEndpoint;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class Users extends RestfulEndpoint
{
    public function __construct($parent)
    {
        parent::__construct(User::class, $parent);
    }

    public function defineMethods()
    {
        $this->get(
            '/me[/]',
            function (ServerRequest $request, Response $response) {
                $token = $request->getAttribute(JWTOperations::ATTR_TOKEN);
                if (false === empty($token[JWTOperations::CLAIM_USER_ID])) {
                    return $response->withJson(
                        User::getInstanceById($request->getAttribute(JWTOperations::ATTR_TOKEN)
                        [JWTOperations::CLAIM_USER_ID])
                            ->toArray($request->getAttribute(IncludeRestfulChildren::ATTR))
                    );
                }
                return $response->withJson(null);
            }
        );
    }
}
