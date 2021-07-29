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
    public function __invoke(ServerRequest $request, Response $response, array $args = []): ResponseInterface
    {
         parent::__invoke($request, $response, $args);
         $queue = Queue::getById($this->getParsedParameter(Queue::foreignKey()), $this->getOAuthUserId(),
             $this->getPDO());
         if ($queue instanceof Queue) {
             $queue->update($request->getParsedBody());
             $queue = $this->recursivelyInclude($queue, $request);
         }
         return $response->withJson($queue);
    }
}
