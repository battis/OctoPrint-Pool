<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\File;
use PDO;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class ListQueue
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(ServerRequest $request, Response $response, array $args = [])
    {
        // TODO there has to be a better way of doing this...
        $statement = $this->pdo->prepare("
            SELECT * FROM `files`
                WHERE
                    `user` = '3dprint' AND
                    `queued` = 1
                ORDER BY
                    `created` ASC,
                    `filename` ASC
        ");
        $files = [];
        if (false !== $statement->execute()) {
            while ($fileData = $statement->fetch()) {
                array_push($files, new File($fileData));
            }
        }
        return $response->withJson($files);
    }
}