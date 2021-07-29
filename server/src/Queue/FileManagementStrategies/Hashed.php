<?php


namespace Battis\OctoPrintPool\Queue\FileManagementStrategies;


use Exception;
use Psr\Http\Message\UploadedFileInterface;

class Hashed extends AbstractStrategy
{
    public function process(
        UploadedFileInterface $uploadedFile,
        string $rootPath,
        string $user_id,
        array $tags = [],
        string $comment = null
    ): string
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        do {
            try {
                $basename = bin2hex(random_bytes(8));
            } catch (Exception $e) {
                // if insufficient entropy, fallback to simpler pseudo-random hash
                $basename = md5(time() . $uploadedFile->getClientFilename());
            }
            $filename = sprintf('%s.%0.8s', $basename, $extension);
        } while (file_exists("$rootPath/$filename"));
        $path = "$rootPath/$filename";
        $uploadedFile->moveTo($path);
        return realpath($path);
    }
}
