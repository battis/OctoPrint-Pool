<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\Objects\File;
use Battis\WebApp\Server\API\Actions\AbstractAction;
use Battis\WebApp\Server\API\Actions\Traits\RecursivelyInclude;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class FilterFiles extends AbstractAction
{
    use RecursivelyInclude;

    /**
     * @throws Exception
     */
    public function handle(ServerRequest $request, Response $response, array $args = []): ResponseInterface
    {
        $queryParams = $request->getQueryParams() ?: [];
        $bodyParams = $request->getParsedBody() ?: [];
        $filter = array_merge($queryParams, $bodyParams, $args);
        $files = File::getByFilter($filter, null, $this->getPDO(), true);
        return $response->withJson($this->recursivelyInclude($files, $request));
    }
}
