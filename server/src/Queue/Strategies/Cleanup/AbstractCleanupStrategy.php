<?php

namespace Battis\OctoPrintPool\Queue\Strategies\Cleanup;

use Battis\Hydratable\Hydratable;
use Battis\OctoPrintPool\Queue\Objects\Queue;
use Monolog\Logger;

abstract class AbstractCleanupStrategy
{
    use Hydratable;

    protected static function cleanableFiles($dir)
    {
        return array_diff(scandir($dir), ['.', '..', '.DAV']);
    }

    protected static function emptyDir($dir, Logger $logger = null)
    {
        $logger && $logger->info('Empty ' . basename($dir) . ' start', ['path' => realpath($dir)]);
        foreach (self::cleanableFiles($dir) as $file) {
            $path = "$dir/$file";
            self::delete($path, $logger);
            $logger && $logger->info("Deleted $file", ['path' => $path]);
        }
        $logger && $logger->info('Empty ' . basename($dir) . ' end', ['path' => realpath($dir)]);
    }

    /**
     * @param string $path
     */
    protected static function delete(string $path, Logger $logger = null): void
    {
        $logger && $logger->info('Delete ' . basename($path) . ' start', ['path' => realpath($path)]);
        if (is_dir($path)) {
            self::emptyDir($path, $logger);
            rmdir($path);
        } else {
            unlink($path);
        }
        $logger && $logger->info('Delete ' . basename($path) . ' end', ['path' => realpath($path)]);
    }

    abstract public function process(Queue $queue, array $params = [], Logger $logger = null);

    public function __invoke(Queue $queue, array $params = [], Logger $logger = null)
    {
        $logger && $logger->info(basename(__CLASS__) . ' start', [
            'queue_id' => $queue->getId(),
            'strategy' => __CLASS__,
            'params' => json_encode($params)
        ]);
        $this->process($queue, $params, $logger);
        $logger && $logger->info(basename(__CLASS__) . ' end', [
            'queue_id' => $queue->getId(),
            'strategy' => __CLASS__,
            'params' => json_encode($params)
        ]);
    }

}
