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

class GetFileInfo extends AbstractAction
{
    use RecursivelyInclude;

    /**
     * @throws Exception
     */
    public function handle(ServerRequest $request, Response $response, array $args = []): ResponseInterface
    {
        return $response->withJson(
            $this->recursivelyInclude(
                File::getById($args[File::foreignKey()], null, $this->getPDO(), true),
                $request,
                [Queue::canonical()],
                true
            )
        );
    }
}
