<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\File;
use Battis\OctoPrintPool\Traits\OAuthUserId;
use Battis\OctoPrintPool\Traits\PdoStorage;
use PDO;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class ListQueue
{
    use PdoStorage, OAuthUserId;

    public function __construct(PDO $pdo)
    {
        $this->setPDO($pdo);
    }

    public function __invoke(ServerRequest $request, Response $response, array $args = [])
    {
        $this->setOauthUserId($request);
        $select = $this->pdo->prepare("
            SELECT * FROM `files`
                WHERE
                    `user_id` = :user_id AND
                    `queued` = 1
                ORDER BY
                    `created` ASC,
                    `filename` ASC
        ");
        $files = [];
        if ($select->execute(['user_id' => $this->oauthUserId])) {
            while ($fileData = $select->fetch()) {
                array_push($files, new File($fileData));
            }
        }
        return $response->withJson($files);
    }
}
