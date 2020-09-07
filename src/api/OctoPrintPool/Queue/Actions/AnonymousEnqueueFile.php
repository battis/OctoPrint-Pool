<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\UserSettings;
use PDO;
use Slim\Exception\HttpForbiddenException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class AnonymousEnqueueFile
{
    use UserSettings;

    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(ServerRequest $request, Response $response, array $args = [])
    {
        if ($this->getUserSetting($this->pdo, $args['user_id'], 'allow_anonymous_upload', false)) {
            $enqueueFile = new EnqueueFile($this->pdo);
            return $enqueueFile($request->withAttribute('user_id', $args['user_id']), $response);
        }
        throw new HttpForbiddenException($request);
    }
}
