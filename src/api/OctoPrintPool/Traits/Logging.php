<?php


namespace Battis\OctoPrintPool\Traits;


use Psr\Log\LoggerInterface;

trait Logging
{
    /** @var LoggerInterface */
    private $logger;

    private function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    private function usernameProxy(array $tags): string
    {
        return (string)array_shift($tags);
    }
}
