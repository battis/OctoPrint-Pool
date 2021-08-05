<?php

namespace Battis\OctoPrintPool\Queue\Actions;

use Battis\OctoPrintPool\Queue\Objects\File;
use Battis\WebApp\Server\API\Actions\AbstractAction;
use Battis\WebApp\Server\API\Actions\Traits\RecursivelyInclude;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class GetMyFiles extends AbstractAction
{
    use RecursivelyInclude;

    /**
     * @throws Exception
     */
    protected function handle(ServerRequest $request, Response $response, array $args = []): ResponseInterface
    {
        return $response->withJson(
            $this->recursivelyInclude(
                File::getByFilter(
                    array_merge(
                        [File::AVAILABLE => true],
                        $request->getQueryParams(),
                        $request->getParsedBody() ?: [],
                        $args
                    ),
                    $request->getAttribute(self::OAUTH_USER_ID),
                    $this->getPDO()
                ),
                $request
            )
        );
    }
}
