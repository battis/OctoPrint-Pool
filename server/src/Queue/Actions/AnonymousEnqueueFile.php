<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\WebApp\Server\OAuth2\Middleware\Authorization;
use Battis\WebApp\Server\OAuth2\Traits\OAuthUserSettings;
use Battis\WebApp\Server\Traits\Logging;
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

    /**
     * @throws HttpForbiddenException
     */
    public function __invoke(ServerRequest $request, Response $response, array $args = [])
    {
        $this->setOauthUserId($args['user_id']);
        if ($this->getUserSetting('queue_allow_anonymous_upload', false)) {
            $enqueueFile = new EnqueueFile($this->pdo, $this->logger);
            return $enqueueFile($request->withAttribute(Authorization::TOKEN_ATTRIBUTE_KEY,
                ['user_id' => $this->getOAuthUserId()])
                , $response);
        } else {
            $this->logger->info("Blocked anonynmous file enqueue {$this->getOAuthUserId()} queue", [
                'tags' => implode(', ', $request->getParsedBodyParam('tags', []))
            ]);
        }
        throw new HttpForbiddenException($request);
    }
}
