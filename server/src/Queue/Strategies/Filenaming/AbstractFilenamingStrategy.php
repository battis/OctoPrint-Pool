<?php


namespace Battis\OctoPrintPool\Queue\Strategies\Filenaming;


use Battis\OctoPrintPool\Queue\Actions\EnqueueFile;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Extensible strategy for managing file uploads
 *
 * Implement {@link AbstractFilenamingStrategy::process()} to determine location and naming of uploaded files. One potential
 * area for extension would be add a filtering capability (e.g. only `.gcode` files will be uploaded).
 *
 * @used-by EnqueueFile used to handle uploaded files
 */
abstract class AbstractFilenamingStrategy
{
    /**
     * @param UploadedFileInterface $uploadedFile
     * @param string $rootPath
     * @param string $user_id
     * @param array $tags (Optional, default `[]`)
     * @param string|null $comment
     * @return string|false Complete path of uploaded file's location or `false` if file move cannot be accomplished
     */
    abstract public function process(
        UploadedFileInterface $uploadedFile,
        string                $rootPath,
        string                $user_id,
        array                 $tags = [],
        string                $comment = null
    ): string;

    protected function appendSequenceNumber(string $rootPath, UploadedFileInterface $uploadedFile)
    {
        $filename = pathinfo($uploadedFile->getClientFilename(), PATHINFO_FILENAME);
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $sequence = 0;
        do {
            $seq = $sequence++ > 0 ? ".$sequence" : '';
            $path = "{$rootPath}/{$filename}{$seq}.{$extension}";
        } while (file_exists($path));
        return $path;
    }

    public function __invoke(
        UploadedFileInterface $uploadedFile,
        string                $rootPath,
        string                $user_id,
        array                 $tags = [],
        string                $comment = null
    )
    {
        return $this->process($uploadedFile, $rootPath, $user_id, $tags, $comment);
    }
}
