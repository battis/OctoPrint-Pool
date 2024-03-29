<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\Objects\File;
use Battis\OctoPrintPool\Queue\Objects\Queue;
use Battis\OctoPrintPool\Queue\Strategies\Filenaming\AbstractFilenamingStrategy;
use Battis\OctoPrintPool\Queue\Strategies\Filenaming\OwnerDirectory;
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
    public function handle(ServerRequest $request, Response $response, array $args = []): ResponseInterface
    {
        $uploadedFiles = $request->getUploadedFiles();
        $tags = array_filter($request->getParsedBodyParam('tags', []), function ($tag) {
            return strlen($tag) > 0;
        });
        $comment = $request->getParsedBodyParam('comment');
        if ($comment === 'null') {
            $comment = null;
        }
        $queue = Queue::getById($args[QUEUE::foreignKey()], null, $this->getPDO(), true);
        $files = [];
        if ($queue instanceof Queue) {
            $rootPath = $queue->getRoot();
            if (!file_exists($rootPath)) {
                mkdir($rootPath);
            }
            $strategy = $queue->getFilenamingStrategy();
            if (!$strategy) {
                $strategy = OwnerDirectory::class;
            }
            /** @var AbstractFilenamingStrategy $strategy */
            $strategy = new $strategy();
            foreach ($uploadedFiles as $uploadedFile) {
                if ($path = $strategy($uploadedFile, $rootPath, $request->getAttribute(self::OAUTH_USER_ID), $tags, $comment)) {
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
