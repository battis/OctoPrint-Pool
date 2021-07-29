<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\Objects\Queue;
use Battis\WebApp\Server\API\Actions\AbstractAction;
use Battis\WebApp\Server\API\Actions\Traits\RecursivelyInclude;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class FilterQueues extends AbstractAction
{
    use RecursivelyInclude;

    /**
     * @throws Exception
     */
    public function __invoke(ServerRequest $request, Response $response, array $args = []): ResponseInterface
    {
        parent::__invoke($request, $response, $args);
        $queryParams = $request->getQueryParams() ?: [];
        $bodyParams = $request->getParsedBody() ?: [];
        $filter = array_merge($queryParams, $bodyParams, $args);
        return $response->withJson(
            $this->recursivelyInclude(
                Queue::getByFilter($filter,null, $this->getPDO(), true),
                $request,
                [],
                true
            )
        );
    }
}
