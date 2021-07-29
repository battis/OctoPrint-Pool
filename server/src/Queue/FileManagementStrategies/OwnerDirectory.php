<?php


namespace Battis\OctoPrintPool\Queue\FileManagementStrategies;


use Psr\Http\Message\UploadedFileInterface;

// FIXME right now this overwrites files, rather than auto-sequencing them
class OwnerDirectory extends AbstractStrategy
{

    public function process(
        UploadedFileInterface $uploadedFile,
        string $rootPath,
        string $user_id,
        array $tags = [],
        string $comment = null
    ): string
    {
        $rootPath = $rootPath . "/$user_id";
        if (!file_exists($rootPath)) {
            mkdir($rootPath);
        }
        $path = $this->appendSequenceNumber($rootPath, $uploadedFile);
        $uploadedFile->moveTo($path);
        return realpath($path);
    }
}
