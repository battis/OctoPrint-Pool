<?php


namespace Battis\OctoPrintPool\Queue;


use Battis\PersistentObject\Parts\Condition;
use Battis\PersistentObject\PersistentObjectException;
use Battis\RestfulAPI\RestfulObject;
use PDO;

/**
 * @method static File[] getInstances(Condition $condition = null, $ordering = null, PDO $pdo = null)
 * @method static File|null getInstanceById($id, Condition $condition = null, PDO $pdo = null)
 * @method static File createInstance(array $values, bool $strict = true, bool $overwrite = false, PDO $pdo = null)
 * @method static File|null deleteInstance(string $id, Condition $condition = null, PDO $pdo = null)
 */
class File extends RestfulObject
{
    const FILENAME = 'filename';
    const PATH = 'path';
    const TAGS = 'tags';
    const COMMENT = 'comment';
    const QUEUED = 'queued';

    /** @var string */
    protected $filename;

    /** @var string */
    protected $path;

    /** @var string[]|null */
    protected $tags;

    /** @var string|null */
    protected $comment;

    /** @var bool */
    protected $queued;

    /**
     * @return string
     * @throws PersistentObjectException
     */
    public function getFilename(): string {
        return $this->getField(self::FILENAME);
    }

    /**
     * @param string $filename
     * @throws PersistentObjectException
     */
    public function setFilename(string $filename) {
        $this->setField(self::FILENAME, $filename);
    }

    /**
     * @return string
     * @throws PersistentObjectException
     */
    public function getPath(): string {
        return $this->getField(self::PATH);
    }

    /**
     * @param string $path
     * @throws PersistentObjectException
     */
    public function setPath(string $path) {
        $this->setField(self::PATH, $path);
    }

    /**
     * @return string[]|null
     * @throws PersistentObjectException
     */
    public function getTags() {
        return $this->getField(self::TAGS);
    }

    /**
     * @param string|string[]|null $tags
     * @throws PersistentObjectException
     */
    public function setTags($tags) {
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }
        $this->setField(self::TAGS, $tags);
    }

    /**
     * @return string|null
     * @throws PersistentObjectException
     */
    public function getComment() {
        return $this->getField(self::COMMENT);
    }

    /**
     * @param string|null $comment
     * @throws PersistentObjectException
     */
    public function setComment($comment) {
        $this->setField(self::COMMENT, $comment);
    }

    /**
     * @return bool
     * @throws PersistentObjectException
     */
    protected function getQueued(): bool {
        return $this->getField(self::QUEUED);
    }

    /**
     * @return bool
     * @throws PersistentObjectException
     */
    public function isQueued(): bool {
        return $this->getQueued();
    }

    /**
     * @param bool $queued
     * @throws PersistentObjectException
     */
    public function setQueued(bool $queued) {
        $this->setField(self::QUEUED, $queued);
    }

    public function toArray(array $fieldsToExpand = [], array $fieldsToSuppress = []): array
    {
        $array = parent::toArray($fieldsToExpand, array_merge($fieldsToSuppress, [self::TAGS, self::QUEUED]));
        $array[self::TAGS] = $this->tags;
        $array[self::QUEUED] = filter_var($this->queued, FILTER_VALIDATE_BOOLEAN);
        return $array;
    }
}
