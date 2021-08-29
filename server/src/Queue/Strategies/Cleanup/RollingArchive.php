<?php

namespace Battis\OctoPrintPool\Queue\Strategies\Cleanup;

use Battis\OctoPrintPool\Queue\Objects\File;
use Battis\OctoPrintPool\Queue\Objects\Queue;
use Monolog\Logger;
use PDO;

class RollingArchive extends AbstractCleanupStrategy
{
    /** @var PDO */
    private $pdo;

    protected static function archive($sourceDir, $targetDir, Logger $logger = null)
    {
        $logger && $logger->info('Archiving from ' . basename($sourceDir) . ' to ' . basename($targetDir) . ' start', [
            'source' => realpath($sourceDir),
            'target' => realpath($targetDir)
        ]);
        if (file_exists($targetDir)) {
            self::emptyDir($targetDir, $logger);
        } else {
            mkdir($targetDir);
            $logger && $logger->info('Created archive directory ' . basename($targetDir), [
                'path' => realpath($targetDir)
            ]);
        }

        foreach (self::cleanableFiles($sourceDir) as $file) {
            $path = "$sourceDir/$file";
            if ($path !== $targetDir) {
                rename($path, "$targetDir/$file"); // FIXME update database
                $logger && $logger->info("Archived $file", ['path' => realpath($path)]);
            }
        }

        $logger && $logger->info('Archiving from ' . basename($sourceDir) . ' to ' . basename($targetDir) . ' end', [
            'source' => realpath($sourceDir),
            'target' => realpath($targetDir)
        ]);
    }

    public function process(Queue $queue, array $params = [], Logger $logger = null)
    {
        $config = self::hydrate($params, [
            'source' => $queue->getRoot(),
            'target' => "{$queue->getRoot()}/Archive"
        ]);
        self::archive($config['source'], $config['target']);
    }
}
