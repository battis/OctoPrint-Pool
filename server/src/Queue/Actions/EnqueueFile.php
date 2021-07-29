<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\FileManagementStrategies\Hashed;
use Battis\OctoPrintPool\Queue\Objects\File;
use Battis\OctoPrintPool\Queue\Objects\Queue;
use Battis\WebApp\Server\API\Actions\AbstractAction;
use Battis\WebApp\Server\API\Actions\Traits\RecursivelyInclude;
use Battis\WebApp\Server\Traits\Logging;
use Exception;
use Monolog\Logger;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class EnqueueFile extends AbstractAction
{
    use RecursivelyInclude, Logging;

    public function __construct(PDO $pdo, Logger $logger)
    {
        parent::__construct($pdo);
        $this->setLogger($logger);
    }

    /**
     * @throws Exception
     */
    public function __invoke(ServerRequest $request, Response $response, array $args = []): ResponseInterface
    {
        parent::__invoke($request, $response, $args);

        $uploadedFiles = $request->getUploadedFiles();
        $tags = array_filter($request->getParsedBodyParam('tags', []), function ($tag) {
            return strlen($tag) > 0;
        });
        $comment = $request->getParsedBodyParam('comment');
        if ($comment === 'null') {
            $comment = null;
        }
        $queue = Queue::getById($this->getParsedParameter(QUEUE::foreignKey()), null, $this->getPDO(), true);
        $files = [];
        if ($queue instanceof Queue) {
            $rootPath = $queue->getRoot();
            if (!$rootPath) {
                $rootPath = $_ENV['VAR_PATH'];
            }
            if (!file_exists($rootPath)) {
                mkdir($rootPath);
            }
            $strategy = $queue->getFileManagementStrategy();
            if (!$strategy) {
                $strategy = Hashed::class;
            }
            $strategy = new $strategy();
            foreach ($uploadedFiles as $uploadedFile) {
                if ($path = $strategy($uploadedFile, $rootPath, $this->getOAuthUserId(), $tags, $comment)) {
                    $file = File::insert(
                        [
                            'queue_id' => $queue->getId(),
                            'filename' => $uploadedFile->getClientFilename(),
                            'path' => $path,
                            'tags' => count($tags) ? implode(',', $tags) : null,
                            'comment' => $comment,
                            'dequeued' => null
                        ],
                        $this->getOAuthUserId(),
                        $this->getPDO()
                    );
                    if ($file instanceof File) {
                        array_push($files, $file);
                        $this->getLogger()->info("Enqueued `{$file->getFilename()}` to {$queue->getName()}", [
                            'queue_id' => $queue->getId(),
                            'file_id' => $file->getId(),
                            'user_id' => $this->getOAuthUserId()
                        ]);
                    }
                }
            }
        }
        return $response->withJson(
            $this->recursivelyInclude(
                $files,
                $request,
                [Queue::canonical()],
                true
            )
        );
    }
}
