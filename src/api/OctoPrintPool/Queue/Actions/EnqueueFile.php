<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\File;
use PDO;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class EnqueueFile
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(ServerRequest $request, Response $response, array $args = [])
    {
        $uploadedFiles = $request->getUploadedFiles();
        $files = [];
        $insert = $this->pdo->prepare("
            INSERT INTO `files`
                SET
                    `user` = :user,
                    `filename` = :filename,
                    `path` = :path,
                    `tags` = :tags,
                    `comment` = :comment
        ");
        $get = $this->pdo->prepare("
            SELECT * FROM `files`
                WHERE
                    `user` = :user AND
                    `id` = :id
        ");
        // TODO filter by extension
        foreach ($uploadedFiles as $uploadedFile) {
            if (false !== $insert->execute([
                    'user' => '3dprint', // FIXME temporary hack
                    'filename' => $uploadedFile->getClientFilename(),
                    'path' => $this->hashUploadedFile($uploadedFile),
                    'tags' => implode(',', $request->getParsedBodyParam('tags', [])),
                    'comment' => $request->getParsedBodyParam('comment')
                ])) {
                if (false !== $get->execute([
                        'user' => '3dprint', // FIXME temporary hack
                        'id' => $this->pdo->lastInsertId()
                    ])) {
                    if (false !== ($fileData = $get->fetch())) {
                        array_push($files, new File($fileData));
                    }
                }
            }
        }
        return $response->withJson($files);
    }

    private function hashUploadedFile(UploadedFileInterface $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8));
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $path = __DIR__ . '/../../../../../var/queue' . DIRECTORY_SEPARATOR . $filename;
        $uploadedFile->moveTo($path);
        return realpath($path);
    }
}
