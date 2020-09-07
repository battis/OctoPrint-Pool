<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\File;
use PDO;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class PeekFile
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(ServerRequest $request, Response $response, array $args = [])
    {
        $select = $this->pdo->prepare("
            SELECT * FROM `files`
                WHERE
                    user_id = :user AND
                    `id` = :id
        ");
        $file = null;
        if ($select->execute([
            'user' => '3dprint', // FIXME temporary hack
            'id' => $args['id']
        ])) {
            if ($fileData = $select->fetch()) {
                $file = new File($fileData);
            }
        }
        return $response->withJson($file);
    }
}
