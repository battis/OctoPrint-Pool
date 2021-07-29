<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\Objects\File;
use Battis\OctoPrintPool\Queue\Objects\Queue;
use Battis\WebApp\Server\API\Actions\AbstractAction;
use Battis\WebApp\Server\API\Actions\Traits\RecursivelyInclude;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class GetQueue extends AbstractAction
{
    use RecursivelyInclude;

    /**
     * @throws Exception
     */
    public function __invoke(ServerRequest $request, Response $response, array $args = []): ResponseInterface
    {
        parent::__invoke($request, $response, $args);
        return $response->withJson(
            $this->recursivelyInclude(
                Queue::getById(
                    $this->getParsedParameter(Queue::foreignKey()),
                    null,
                    $this->getPDO(),
                    true
                ),
                $request,
                [],
                true
            )
        );
    }
}
