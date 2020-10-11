<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\File;
use Battis\OctoPrintPool\Queue\FileManagementStrategies\Hashed;
use Battis\OctoPrintPool\Traits\Logging;
use Battis\OctoPrintPool\Traits\OAuthUserSettings;
use Monolog\Logger;
use PDO;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

/**
 * @uses \Battis\OctoPrintPool\Queue\FileManagementStrategies\AbstractStrategy for file management strategy
 */
class EnqueueFile
{
    use OAuthUserSettings, Logging;

    public function __construct(PDO $pdo, Logger $logger)
    {
        $this->setPDO($pdo);
        $this->setLogger($logger);
    }

    public function __invoke(ServerRequest $request, Response $response, array $args = [])
    {
        $this->setOauthUserId($request);
        $uploadedFiles = $request->getUploadedFiles();
        $rootPath = $this->getUserSetting('queue_root', __DIR__ . '/../../../../../var/queue');
        $strategy = $this->getUserSetting('queue_file_management_strategy', Hashed::class);
        $strategy = new $strategy();
        $tags = $request->getParsedBodyParam('tags', []);
        $comment = $request->getParsedBodyParam('comment');
        $files = [];
        $insert = $this->pdo->prepare("
            INSERT INTO `files`
                SET
                    `user_id` = :user_id,
                    `filename` = :filename,
                    `path` = :path,
                    `tags` = :tags,
                    `comment` = :comment
        ");
        $get = $this->pdo->prepare("
            SELECT * FROM `files`
                WHERE
                    `user_id` = :user_id AND
                    `id` = :id
        ");
        foreach ($uploadedFiles as $uploadedFile) {
            if ($path = $strategy($uploadedFile, $rootPath, $tags, $comment)) {
                if ($insert->execute([
                    'user_id' => $this->oauthUserId,
                    'filename' => $uploadedFile->getClientFilename(),
                    'path' => $path,
                    'tags' => implode(',', $tags),
                    'comment' => $comment
                ])) {
                    if ($get->execute([
                        'user_id' => $this->oauthUserId,
                        'id' => $this->pdo->lastInsertId()
                    ])) {
                        if ($fileData = $get->fetch()) {
                            $file = new File($fileData);
                            array_push($files, $file);
                            $this->logger->info("Enqueued `{$file->getFilename()}` to {$this->oauthUserId} queue", [
                                'file_id' => $file->getId(),
                                'username_proxy' => $this->usernameProxy($file->getTags())
                            ]);
                        }
                    }
                }
            }
        }
        return $response->withJson($files);
    }
}
