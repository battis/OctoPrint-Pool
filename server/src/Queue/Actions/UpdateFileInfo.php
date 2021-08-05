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
    public function handle(ServerRequest $request, Response $response, array $args = []): ResponseInterface
    {
        $file = File::getById($args[File::foreignKey()], $request->getAttribute(self::OAUTH_USER_ID), $this->getPDO());
        if ($file instanceof File) {
            $file->update($request->getParsedBody());
            $file = $this->recursivelyInclude($file, $request, [Queue::canonical()], true);
        }
        return $response->withJson($file);
    }
}
