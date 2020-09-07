<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\OctoPrintPool\Queue\File;
use Battis\OctoPrintPool\UserSettings;
use PDO;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class EnqueueFile
{
    use UserSettings;

    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(ServerRequest $request, Response $response, array $args = [])
    {
        $user_id = $request->getAttribute('user_id', '3dprint'); // FIXME temporary hack
        $path = __DIR__ . '/../../../../../var/queue';
        $uploadedFiles = $request->getUploadedFiles();
        $hashed = $this->getUserSetting($this->pdo, $user_id, 'queue_hashed', true, [$this, 'forceBoolean']);
        if (!$hashed) {
            $path = $this->getUserSetting($this->pdo, $user_id, 'queue_root', $path);
        }
        $tags = $request->getParsedBodyParam('tags', []);
        $files = [];
        $insert = $this->pdo->prepare("
            INSERT INTO `files`
                SET
                    `user_id` = :user,
                    `filename` = :filename,
                    `path` = :path,
                    `tags` = :tags,
                    `comment` = :comment
        ");
        $get = $this->pdo->prepare("
            SELECT * FROM `files`
                WHERE
                    `user_id` = :user AND
                    `id` = :id
        ");
        // TODO filter by extension
        // FIXME $path will just keep growing for multiple files
        foreach ($uploadedFiles as $uploadedFile) {
            if ($hashed) {
                $path = $this->hashUploadedFile($uploadedFile, $path);
            } else {
                if ($this->getUserSetting($this->pdo, $user_id, 'queue_hierarchy', true, [$this, 'forceBoolean'])) {
                    $path = $this->pathFromTags($uploadedFile, $path, $tags);
                } elseif ($this->getUserSetting($this->pdo, $user_id, 'queue_sequential', false, [$this, 'forceBoolean'])) {
                    $path = $this->addToSequence($uploadedFile, $path, $tags);
                } else {
                    $path .= '/' . $uploadedFile->getClientFilename();
                    $uploadedFile->moveTo($path);
                    $path = realpath($path);
                }
            }
            if ($insert->execute([
                'user' => $user_id,
                'filename' => $uploadedFile->getClientFilename(),
                'path' => $path,
                'tags' => implode(',', $tags),
                'comment' => $request->getParsedBodyParam('comment')
            ])) {
                if ($get->execute([
                    'user' => $user_id,
                    'id' => $this->pdo->lastInsertId()
                ])) {
                    if ($fileData = $get->fetch()) {
                        array_push($files, new File($fileData));
                    }
                }
            }
        }
        return $response->withJson($files);
    }

    private function hashUploadedFile(UploadedFileInterface $uploadedFile, string $path): string
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        do {
            $basename = bin2hex(random_bytes(8));
            $filename = sprintf('%s.%0.8s', $basename, $extension);
        } while (file_exists("$path/$filename"));
        $path .= "/$filename";
        $uploadedFile->moveTo($path);
        return realpath($path);
    }

    private function pathFromTags(UploadedFileInterface $uploadedFile, string $path, array $tags = []): string
    {
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

    private function addToSequence(UploadedFileInterface $uploadedFile, string $path, array $tags = []): string
    {
        $sequence = 0;
        foreach (scandir($path) as $item) {
            $sequence = max($sequence, (int)preg_replace('/^(\d+)/', '$1', basename($item)));
        }
        $sequence++;
        $basename = pathinfo($uploadedFile->getClientFilename(), PATHINFO_BASENAME);
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $path = $path . '/' . sprintf('%04d', $sequence) . ' ' . $basename . (empty($tags) ? '' : ' (' . implode(', ',
                    $tags) . ')') . '.' . $extension;
        $uploadedFile->moveTo($path);
        return realpath($path);
    }
}
