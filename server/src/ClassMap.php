<?php


namespace Battis\OctoPrintPool;


use Battis\OctoPrintPool\Queue\Objects\File;
use Battis\OctoPrintPool\Queue\Objects\Queue;
use Battis\WebApp\Server\API\Objects\AbstractObject;
use Battis\WebApp\Server\OAuth2\Objects\User;

class ClassMap implements \Battis\WebApp\Server\API\Objects\ClassMap
{

    public static function foreignKeyToClass(string $key)
    {
        switch ($key) {
            case User::foreignKey():
                return User::class;
            case Queue::foreignKey():
                return Queue::class;
            default:
                return null;
        }
    }

    public static function canonicalToClass(string $name)
    {
        switch($name) {
            case File::canonical():
                return File::class;
            case Queue::canonical():
                return Queue::class;
            case User::canonical():
                return User::class;
            default:
                return null;
        }
    }

    public static function pluralToClass(string $name)
    {
        switch ($name) {
            case File::plural():
                return File::class;
            case Queue::plural():
                return Queue::class;
            case User::plural():
                return User::class;
            default:
                return null;
        }
    }
}
