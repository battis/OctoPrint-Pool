<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\File;
use Battis\OctoPrintPool\Queue\FileManagementStrategies\Hashed;
use Battis\OctoPrintPool\Traits\OAuthUserSettings;
use PDO;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

/**
 * @uses \Battis\OctoPrintPool\Queue\FileManagementStrategies\AbstractStrategy for file management strategy
 */
class EnqueueFile
{
    use OAuthUserSettings;

    public function __construct(PDO $pdo)
    {
        $this->setPDO($pdo);
    }

    public function __invoke(ServerRequest $request, Response $response, array $args = [])
    {
        $this->setOauthUserId($request);
        $uploadedFiles = $request->getUploadedFiles();
        $rootPath = $this->getUserSetting('queue_root', __DIR__ . '/../../../../../var/queue');
        $strategy = $this->getUserSetting('queue_file_management_strategy', Hashed::class);
        $strategy = new $strategy();
        $tags = $request->getParsedBodyParam('tags', []);
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
            if ($path = $strategy($uploadedFile, $rootPath, $tags)) {
                if ($insert->execute([
                    'user_id' => $this->oauthUserId,
                    'filename' => $uploadedFile->getClientFilename(),
                    'path' => $path,
                    'tags' => implode(',', $tags),
                    'comment' => $request->getParsedBodyParam('comment')
                ])) {
                    if ($get->execute([
                        'user_id' => $this->oauthUserId,
                        'id' => $this->pdo->lastInsertId()
                    ])) {
                        if ($fileData = $get->fetch()) {
                            array_push($files, new File($fileData));
                        }
                    }
                }
            }
        }
        return $response->withJson($files);
    }
}
