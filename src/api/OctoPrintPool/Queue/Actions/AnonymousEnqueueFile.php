<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Traits\OAuthUserSettings;
use PDO;
use Slim\Exception\HttpForbiddenException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class AnonymousEnqueueFile
{
    use OAuthUserSettings;

    public function __construct(PDO $pdo)
    {
        $this->setPDO($pdo);
    }

    public function __invoke(ServerRequest $request, Response $response, array $args = [])
    {
        $this->setOauthUserId($args['user_id']);
        if ($this->getUserSetting('queue_allow_anonymous_upload', false)) {
            $enqueueFile = new EnqueueFile($this->pdo);
            return $enqueueFile($request->withAttribute('user_id', $this->oauthUserId), $response);
        }
        throw new HttpForbiddenException($request);
    }
}
