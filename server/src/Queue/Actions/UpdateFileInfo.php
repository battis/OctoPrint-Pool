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

class UpdateFileInfo extends AbstractAction
{
    use RecursivelyInclude;

    /**
     * @throws Exception
     */
    public function __invoke(ServerRequest $request, Response $response, array $args = []): ResponseInterface
    {
        parent::__invoke($request, $response, $args);
        $file = File::getById($this->getParsedParameter(File::foreignKey()), $this->getOAuthUserId(), $this->getPDO());
        if ($file instanceof File) {
            $file->update($request->getParsedBody());
            $file = $this->recursivelyInclude($file, $request, [Queue::canonical()], true);
        }
        return $response->withJson($file);
    }
}
