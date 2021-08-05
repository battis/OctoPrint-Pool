<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\Objects\Queue;
use Battis\WebApp\Server\API\Actions\AbstractAction;
use Battis\WebApp\Server\API\Actions\Traits\RecursivelyInclude;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class UpdateQueue extends AbstractAction
{
    use RecursivelyInclude;

    /**
     * @throws Exception
     */
    public function handle(ServerRequest $request, Response $response, array $args = []): ResponseInterface
    {
        $queue = Queue::getById($args[Queue::foreignKey()], $request->getAttribute(self::OAUTH_USER_ID),
            $this->getPDO());
        if ($queue instanceof Queue) {
            $queue->update($request->getParsedBody());
            $queue = $this->recursivelyInclude($queue, $request);
        }
        return $response->withJson($queue);
    }
}
