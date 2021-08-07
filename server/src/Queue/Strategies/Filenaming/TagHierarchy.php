<?php


namespace Battis\OctoPrintPool\Queue\Strategies\Filenaming;


use Psr\Http\Message\UploadedFileInterface;

// FIXME right now this overwrites files, rather than auto-sequencing them
class TagHierarchy extends AbstractFilenamingStrategy
{

    public function process(
        UploadedFileInterface $uploadedFile,
        string $rootPath,
        string $user_id,
        array $tags = [],
        string $comment = null
    ): string
    {
        foreach ($tags as $tag) {
            $rootPath .= "/$tag";
            if (!file_exists($rootPath)) {
                mkdir($rootPath);
            }
        }
        $path = $this->appendSequenceNumber($rootPath, $uploadedFile);
        $uploadedFile->moveTo($path);
        return realpath($path);
    }
}
