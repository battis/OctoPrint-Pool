<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\File;
use PDO;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class DequeueFile
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(ServerRequest $request, Response $response, array $args = [])
    {
        $update = $this->pdo->prepare("
            UPDATE `files`
                SET
                    `queued` = 0
                WHERE
                    `user` = :user AND
                    `id` = :id AND
                    `queued` = 1
        ");
        $get = $this->pdo->prepare("
            SELECT * FROM `files`
                WHERE
                    `user` = :user AND
                    `id` = :id
        ");
        $file = null;
        if ($update->execute([
                'user' => '3dprint', // FIXME temporary hack
                'id' => $args['id']
            ]) && $update->rowCount() > 0) {
            if ($get->execute([
                'user' => '3dprint', // FIXME temporary hack
                'id' => $args['id']
            ])) {
                {
                    if ($fileData = $get->fetch()) {
                        $file = new File($fileData);
                        $response = $response->withFileDownload($fileData['path'], $fileData['filename']);
                    }
                }
            }
        }
        return $response->withJson($file);
    }
}
