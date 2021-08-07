<?php

namespace Battis\OctoPrintPool\Queue\Strategies\Cleanup;

use Battis\OctoPrintPool\Queue\Objects\Queue;
use DateTime;
use Exception;
use Monolog\Logger;

class PurgeExpired extends AbstractCleanupStrategy
{
    protected function purge($purgeDir, DateTime $expiration, Logger $logger = null) {
        foreach(self::cleanableFiles($purgeDir) as $file) {
            $path = "$purgeDir/$file";
            if ($expiration->getTimestamp() > filectime($path)) {
                $logger && $logger->info('Deleting ' . basename($path), [
                    'path' => realpath($path),
                    'reason' => 'expired per filesystem change date'
                ]);
                self::delete($path, $logger);
            }
        }
    }

    public function process(Queue $queue, array $params = [], Logger $logger = null)
    {
        $config = self::hydrate($params, ['expiration' => '2 weeks ago']);

        try {
            if ($expiration = new DateTime($config['expiration'])) {
                /* purge files based on database information */
                foreach ($queue->getAvailableFiles() as $file) {
                    if ($file->getCreated() < $expiration) {
                        unlink($file->getPath());
                        $logger && $logger->info("Delete {$file->getFilename()}", [
                            'path' => realpath($file->getPath()),
                            'file_id' => $file->getId(),
                            'queue_id' => $file->getQueueId(),
                            'user_id' => $file->getUserId(),
                            'reason' => 'expired per database entry'
                        ]);
                    }
                }

                /* purge any additional files */
                self::purge($queue->getRoot(), $expiration, $logger);
            }
        } catch (Exception $e) {
            // do nothing
        }
    }
}
