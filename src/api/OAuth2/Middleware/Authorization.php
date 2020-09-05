<?php


namespace Battis\OAuth2\Middleware;


use Chadicus\Slim\OAuth2\Http\RequestBridge;
use Chadicus\Slim\OAuth2\Http\ResponseBridge;
use OAuth2\Server;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Authorization implements MiddlewareInterface
{
    const TOKEN_ATTRIBUTE_KEY = 'oauth2-token';

    /** @var Server */
    private $server;

    /** @var array */
    private $scopes;

    public function __construct(Server $server, array $scopes = []) {
        $this->server = $server;
        $this->scopes = $this->formatScopes($scopes);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $oauth2Request = RequestBridge::toOAuth2($request);
        foreach($this->scopes as $scope) {
            if ($this->server->verifyResourceRequest($oauth2Request, null, $scope)) {
                return $handler->handle($request->withAttribute(self::TOKEN_ATTRIBUTE_KEY,
                    $this->server->getResourceController()->getToken()));
            }
        }

        return ResponseBridge::fromOauth2($this->server->getResponse());
    }

    public function withRequiredScope(array $scopes) {
        $clone = clone $this;
        $clone->scopes = $clone->formatScopes($scopes);
        return $clone;
    }

    private function formatScopes(array $scopes) {
        if (empty($scopes)) {
            return [null];
        }

        array_walk(
            $scopes,
            function (&$scope) {
                if (is_array($scope)) {
                    $scope = implode(' ', $scope);
                }
            }
        );

        return $scopes;
    }
}
