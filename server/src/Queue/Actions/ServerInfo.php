<?php


namespace Battis\OctoPrintPool\Queue\Actions;


use Battis\WebApp\Server\API\Actions\AbstractAction;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class ServerInfo extends AbstractAction
{
    // https://stackoverflow.com/a/25370978/294171

    // Returns a file size limit in bytes based on the PHP upload_max_filesize
    // and post_max_size
    private static function file_upload_max_size()
    {
        static $max_size = -1;

        if ($max_size < 0) {
            // Start with post_max_size.
            $post_max_size = self::parse_size(ini_get('post_max_size'));
            if ($post_max_size > 0) {
                $max_size = $post_max_size;
            }

            // If upload_max_size is less, then reduce. Except if upload_max_size is
            // zero, which indicates no limit.
            $upload_max = self::parse_size(ini_get('upload_max_filesize'));
            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }
        return $max_size;
    }

    private static function parse_size($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }

    // SDB
    private static function pretty_file_upload_max_size()
    {
        $bytes = self::file_upload_max_size();
        if (($kb = $bytes / 1024) > 1) {
            if (($mb = $kb / 1024) > 1) {
                if (($gb = $mb / 1024) > 1) {
                    return "{$gb}GB";
                }
                return "{$mb}MB";
            }
            return "{$kb}KB";
        }
        return "$bytes bytes";
    }


    public function __invoke(ServerRequest $request, Response $response, array $args = []): Response
    {
        return $response->withJson(['max_upload_size' => self::pretty_file_upload_max_size()]);
    }
}
