<?php


namespace Battis\OctoPrintPool\Queue\FileManagementStrategies;


use Psr\Http\Message\UploadedFileInterface;

class Sequential extends AbstractStrategy
{

    public function process(
        UploadedFileInterface $uploadedFile,
        string $rootPath,
        string $user_id,
        array $tags = [],
        string $comment = null
    ): string
    {
        $sequence = 0;
        foreach (scandir($rootPath) as $item) {
            $sequence = max($sequence, (int)preg_replace('/^(\d+)/', '$1', basename($item)));
        }
        $sequence = sprintf('%04d', ++$sequence);
        $filename = pathinfo($uploadedFile->getClientFilename(), PATHINFO_FILENAME);
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $tags = empty($tags) ? '' : ' [' . implode(', ', $tags) . ']';
        $comment = empty($comment) ? '' : " - $comment";
        $path = "{$rootPath}/{$sequence} {$filename}{$tags}{$comment}.{$extension}";
        $uploadedFile->moveTo($path);
        return realpath($path);
    }
}
