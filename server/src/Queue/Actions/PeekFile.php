<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\File;
use Battis\WebApp\Server\OAuth2\Traits\OAuthUserId;
use Battis\WebApp\Server\Traits\PdoStorage;
use PDO;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class PeekFile
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
                    `id` = :id
        ");
        $file = null;
        if ($select->execute([
            'user_id' => $this->oauthUserId,
            'id' => $args['id']
        ])) {
            if ($fileData = $select->fetch()) {
                $file = new File($fileData);
            }
        }
        return $response->withJson($file);
    }
}
