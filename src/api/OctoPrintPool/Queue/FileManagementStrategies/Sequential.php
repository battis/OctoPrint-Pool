<?php


namespace Battis\OctoPrintPool\Queue\FileManagementStrategies;


use Psr\Http\Message\UploadedFileInterface;

class Sequential extends AbstractStrategy
{

    public function process(UploadedFileInterface $uploadedFile, string $rootPath, array $tags = []): string
    {
        $sequence = 0;
        foreach (scandir($rootPath) as $item) {
            $sequence = max($sequence, (int)preg_replace('/^(\d+)/', '$1', basename($item)));
        }
        $sequence++;
        $basename = pathinfo($uploadedFile->getClientFilename(), PATHINFO_BASENAME);
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $path = $rootPath . '/' . sprintf('%04d', $sequence) . ' ' . $basename . (empty($tags) ? '' : ' (' . implode(', ',
                    $tags) . ')') . '.' . $extension;
        $uploadedFile->moveTo($path);
        return realpath($path);
    }
}
