<?php


namespace Battis\OctoPrintPool\Queue\FileManagementStrategies;


use Psr\Http\Message\UploadedFileInterface;

class Sequential extends AbstractStrategy
{

    public function process(
        UploadedFileInterface $uploadedFile,
        string $rootPath,
        array $tags = [],
        string $comment = null
    ): string
    {
        $sequence = 0;
        foreach (scandir($rootPath) as $item) {
            $sequence = max($sequence, (int)preg_replace('/^(\d+)/', '$1', basename($item)));
        }
        $sequence++;
        $filename = pathinfo($uploadedFile->getClientFilename(), PATHINFO_FILENAME);
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $path = $rootPath . '/' . sprintf('%04d', $sequence) . ' ' . $filename . (empty($tags) ? '' : ' (' . implode(', ',
                    $tags) . ')') . (empty($comment) ? '' : " - $comment") . '.' . $extension;
        $uploadedFile->moveTo($path);
        return realpath($path);
    }
}
