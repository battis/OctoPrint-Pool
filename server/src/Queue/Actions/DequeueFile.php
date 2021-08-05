<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\Objects\File;
use Battis\OctoPrintPool\Queue\Objects\Queue;
use Battis\WebApp\Server\API\Actions\AbstractAction;
use Battis\WebApp\Server\Traits\Logging;
use DateTimeImmutable;
use Exception;
use Monolog\Logger;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class DequeueFile extends AbstractAction
{
    use Logging;

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
        $file = File::getById($args[File::foreignKey()], null, $this->getPDO(), true);
        if ($file instanceof File && $file->isQueued()) {
            $queue = Queue::getById($file->getQueueId(), null, $this->getPDO(), true);
            if ($queue instanceof Queue) {
                if ($file->isAvailable()) {
                    $this->getLogger()->info("Dequeued {$file->getFilename()} from {$queue->getName()}", [
                        'queue_id' => $queue->getId(),
                        'file_id' => $file->getId(),
                        'user_id' => $this->getOAuthUserId()
                    ]);
                    $file->update([
                        'queued' => 0,
                        'dequeued' => (new DateTimeImmutable())->format('Y-m-d H:i:s')
                    ]);
                    return $response->withFileDownload($file->getPath(), $file->getFilename());
                } else {
                    $this->getLogger()->warning("Failed to dequeue {$file->getFilename()} from {$queue->getName()}: no longer available", [
                        'queue_id' => $queue->getId(),
                        'file_id' => $file->getId(),
                        'user_id' => $this->getOAuthUserId()
                    ]);
                    return $response->withJson(['error' => 'file no longer available'])->withStatus(410);
                }
            }
        }
        return $response->withStatus(404);
    }
}
