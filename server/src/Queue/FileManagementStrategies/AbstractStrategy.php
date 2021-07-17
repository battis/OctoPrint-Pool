<?php


namespace Battis\OctoPrintPool\Queue\FileManagementStrategies;


use Battis\OctoPrintPool\Queue\Actions\EnqueueFile;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Extensible strategy for managing file uploads
 *
 * Implement {@link AbstractStrategy::process()} to determine location and naming of uploaded files. One potential
 * area for extension would be add a filtering capability (e.g. only `.gcode` files will be uploaded).
 *
 * @used-by EnqueueFile used to handle uploaded files
 */
abstract class AbstractStrategy
{
    /**
     * @param UploadedFileInterface $uploadedFile
     * @param string $rootPath
     * @param array $tags (Optional, default `[]`)
     * @param string|null $comment
     * @return string|false Complete path of uploaded file's location or `false` if file move cannot be accomplished
     */
    abstract public function process(
        UploadedFileInterface $uploadedFile,
        string $rootPath,
        array $tags = [],
        string $comment = null
    ): string;

    public function __invoke(UploadedFileInterface $uploadedFile, string $rootPath, array $tags = [], string $comment
    = null)
    {
        return $this->process($uploadedFile, $rootPath, $tags, $comment);
    }
}
