<?php


namespace Battis\OctoPrintPool\Routes;


use Battis\OctoPrintPool\Queue\File;
use Battis\PersistentObject\Parts\Condition;
use Battis\RestfulAPI\Routing\RestfulEndpoint;
use Battis\RestfulAPI\Routing\RestfulEndpointException;
use Exception;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class Queue extends RestfulEndpoint
{
    public function __construct($parent)
    {
        parent::__construct('queue', $parent);
    }

    private function moveUploadedFile(UploadedFileInterface $uploadedFile) {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8));
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $path = __DIR__ . '/../../../../var/queue' . DIRECTORY_SEPARATOR . $filename;
        $uploadedFile->moveTo($path);
        return realpath($path);
    }

    /**
     * @throws RestfulEndpointException
     */
    public function defineMethods()
    {
        $this->post(
            '[/]',
            function (ServerRequest $request, Response $response) {
                $uploadedFiles = $request->getUploadedFiles();
                $files = [];
                if (is_array($uploadedFiles['file'])) {
                    $uploadedFiles = $uploadedFiles['file'];
                }
                foreach($uploadedFiles as $uploadedFile) {
                    try {
                        array_push($files, File::createInstance([
                            'filename' => $uploadedFile->getClientFilename(),
                            'upload_user' => $request->getParsedBodyParam('user'),
                            'path' => $this->moveUploadedFile($uploadedFile),
                            'comment' => $request->getParsedBodyParam('comment'),
                            'queued' => true
                        ])->toArray());
                    } catch (Exception $e) {
                        // FIXME this is sloppy
                        error_log($e->getMessage());
                    }
                }
                return $response->withJson($files);
            }
        );
        $this->get(
            '[/]',
            function (ServerRequest $request, Response $response) {
                return $response->withJson(File::getInstances(Condition::fromPairedValues(['queued' => true])));
            }
        );
        $this->delete(
            '/{id}',
            function (ServerRequest $request, Response $response, array $args) {
                $file = File::getInstanceById($args['id']);
                $file->setQueued(false);
                echo $file->isQueued();
                return $response->withFileDownload($file->getPath(), $file->getFileName(), true);
            }
        );
    }
}
