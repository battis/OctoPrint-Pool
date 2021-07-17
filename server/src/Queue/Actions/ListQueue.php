<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\File;
use Battis\WebApp\Server\OAuth2\Traits\OAuthUserId;
use Battis\WebApp\Server\Traits\PdoStorage;
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
        $filter = $request->getParsedBody();
        $filter['user_id'] = $this->oauthUserId;
        if (!isset($filter['queued'])) {
            $filter['queued'] = 1;
        }
        $filterConditions = join(' AND ', array_map(function ($key) {
            return "`$key` = :$key";
        }, array_keys($filter)));
        $select = $this->pdo->prepare('
            SELECT * FROM `files`
                WHERE ' . $filterConditions . '
                ORDER BY
                    `created`,
                    `filename`
        ');
        $files = [];
        if ($select->execute($filter)) {
            while ($fileData = $select->fetch()) {
                array_push($files, new File($fileData));
            }
        }
        return $response->withJson($files);
    }
}
