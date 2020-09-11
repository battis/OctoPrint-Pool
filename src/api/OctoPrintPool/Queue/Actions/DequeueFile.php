<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\File;
use Battis\OctoPrintPool\Traits\Logging;
use Battis\OctoPrintPool\Traits\OAuthUserId;
use Battis\OctoPrintPool\Traits\PdoStorage;
use Monolog\Logger;
use PDO;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class DequeueFile
{
    use PdoStorage, OAuthUserId, Logging;

    public function __construct(PDO $pdo, Logger $logger)
    {
        $this->setPDO($pdo);
        $this->setLogger($logger);
    }

    public function __invoke(ServerRequest $request, Response $response, array $args = [])
    {
        $this->setOauthUserId($request);
        $update = $this->pdo->prepare("
            UPDATE `files`
                SET
                    `queued` = 0
                WHERE
                    user_id = :user_id AND
                    `id` = :id AND
                    `queued` = 1
        ");
        $get = $this->pdo->prepare("
            SELECT * FROM `files`
                WHERE
                    user_id = :user_id AND
                    `id` = :id
        ");
        $file = null;
        if ($update->execute([
                'user_id' => $this->oauthUserId,
                'id' => $args['id']
            ]) && $update->rowCount() > 0) {
            if ($get->execute([
                'user_id' => $this->oauthUserId,
                'id' => $args['id']
            ])) {
                {
                    if ($fileData = $get->fetch()) {
                        $file = new File($fileData);
                        $response = $response->withFileDownload($fileData['path'], $fileData['filename']);
                        $this->logger->info("Dequeued `{$file->getFilename()}` from {$this->oauthUserId} queue", [
                            'file_id' => $file->getId(),
                            'username_proxy' => $this->usernameProxy($file->getTags())
                        ]);
                    }
                }
            }
        }
        return $response;
    }
}
