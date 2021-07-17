<?php


namespace Battis\OctoPrintPool\Queue\FileManagementStrategies;


use Psr\Http\Message\UploadedFileInterface;

class TagHierarchy extends AbstractStrategy
{

    public function process(
        UploadedFileInterface $uploadedFile,
        string $rootPath,
        array $tags = [],
        string $comment = null
    ): string
    {
        $path = $rootPath;
        foreach ($tags as $tag) {
            $path .= "/$tag";
            if (!file_exists($path)) {
                mkdir($path);
            }
        }
        $path .= '/' . $uploadedFile->getClientFilename();
        $uploadedFile->moveTo($path);
        return realpath($path);
    }
}
