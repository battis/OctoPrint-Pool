<?php


namespace Battis\OctoPrintPool\Traits;


use Psr\Http\Message\ServerRequestInterface;

trait OAuthUserId
{
    /** @var string */
    private $oauthUserId;

    /**
     * @param string|ServerRequestInterface $oauthUserId_or_serverRequest
     * @param string $default
     */
    private function setOauthUserId($oauthUserId_or_serverRequest, string $default = '3dprint') // FIXME temporary hack
    {
        if (is_a($oauthUserId_or_serverRequest, ServerRequestInterface::class)) {
            $oauthUserId_or_serverRequest = $oauthUserId_or_serverRequest->getAttribute('user_id', $default);
        }
        $this->oauthUserId = (string)$oauthUserId_or_serverRequest;
    }
}
