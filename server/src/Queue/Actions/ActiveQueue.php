<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\Objects\File;
use Battis\WebApp\Server\API\Actions\AbstractAction;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class ActiveQueue extends AbstractAction
{
    public function __invoke(ServerRequest $request, Response $response, array $args = []): ResponseInterface
    {
        parent::__invoke($request, $response, $args);
        return $response->withJson(
            array_filter(
                File::getByFilter(
                    [
                        File::QUEUED => true,
                        File::AVAILABLE => true,
                        File::QUEUE_ID => $this->getParsedParameter(File::QUEUE_ID)
                    ],
                    null,
                    $this->getPDO(),
                    true
                ),
                function ($file) {
                    return $file->isAvailable();
                }
            )
        );
    }
}
