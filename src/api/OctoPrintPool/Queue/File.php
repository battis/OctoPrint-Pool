<?php


namespace Battis\OctoPrintPool\Queue;


use DateTimeImmutable;
use Exception;
use JsonSerializable;

class File implements JsonSerializable
{
    /** @var string */
    private $id;

    /** @var string */
    private $user;

    /** @var string */
    private $filename;

    /** @var string */
    private $path;

    /** @var string[] */
    private $tags;

    /** @var string|null */
    private $comment;

    /** @var bool */
    private $queued;

    /** @var DateTimeImmutable | string */
    private $created;

    /** @var DateTimeImmutable | string */
    private $modified;

    /**
     * File constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        foreach ($data as $property => $value) {
            switch ($property) {
                case 'tags':
                    $this->tags = array_map('trim', explode(',', $value));
                    break;
                case 'queued':
                    $this->queued = boolval($value);
                    break;
                case 'created':
                    /**
                     * @noinspection PhpMissingBreakStatementInspection
                     * fall-through on DateTimeImmutable fail to default behavior
                     */
                case 'modified':
                    try {
                        $this->$property = new DateTimeImmutable($value);
                        break;
                    } catch (Exception $e) {
                    }
                default:
                    $this->$property = $value;
            }
        }
    }

    public function jsonSerialize()
    {
        $result = [];
        foreach ($this as $property => $value) {
            switch ($property) {
                case 'user':
                case 'path':
                    break;
                case 'created':
                case 'modified':
                    $result[$property] = $this->$property->format(DateTimeImmutable::ISO8601);
                    break;
                default:
                    $result[$property] = $value;
            }
        }
        return $result;
    }
}
