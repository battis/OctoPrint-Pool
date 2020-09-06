<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use PDO;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class AnonymousEnqueueFile
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(ServerRequest $request, Response $response, array $args = [])
    {
        $enqueueFile = new EnqueueFile($this->pdo);
        return $enqueueFile($request->withAttribute('user_id', $args['user_id']), $response);
    }
}
