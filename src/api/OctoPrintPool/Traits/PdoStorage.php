<?php


namespace Battis\OctoPrintPool\Traits;


use PDO;

trait PdoStorage
{
    /** @var PDO */
    private $pdo;

    private function setPDO(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}
