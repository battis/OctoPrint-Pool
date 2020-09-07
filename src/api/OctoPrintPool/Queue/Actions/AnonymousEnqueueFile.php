<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Traits\Logging;
use Battis\OctoPrintPool\Traits\OAuthUserSettings;
use Monolog\Logger;
use PDO;
use Slim\Exception\HttpForbiddenException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class AnonymousEnqueueFile
{
    use OAuthUserSettings, Logging;

    public function __construct(PDO $pdo, Logger $logger)
    {
        $this->setPDO($pdo);
        $this->setLogger($logger);
    }

    public function __invoke(ServerRequest $request, Response $response, array $args = [])
    {
        $this->setOauthUserId($args['user_id']);
        if ($this->getUserSetting('queue_allow_anonymous_upload', false)) {
            $enqueueFile = new EnqueueFile($this->pdo, $this->logger);
            return $enqueueFile($request->withAttribute('user_id', $this->oauthUserId), $response);
        } else {
            $this->logger->info("Blocked anonynmous file enqueue {$this->oauthUserId} queue", [
                'tags' => implode(', ', $request->getParsedBodyParam('tags', []))
            ]);
        }
        throw new HttpForbiddenException($request);
    }
}
