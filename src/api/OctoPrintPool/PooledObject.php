<?php


namespace Battis\OctoPrintPool;


use Battis\RestfulAPI\RestfulObject;

class PooledObject extends RestfulObject
{
    protected static $USER_BINDING = User::class;
}
