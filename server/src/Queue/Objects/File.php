<?php


namespace Battis\OctoPrintPool\Queue\Objects;


use Battis\WebApp\Server\API\Objects\AbstractObject;
use Battis\WebApp\Server\Traits\ScalarToBoolean;
use DateTimeImmutable;
use Exception;
use PDO;

/**
 * @method static File|null insert(array $data, ?string $user_id, PDO $pdo, bool $dangerouslyDisregardUserId = false)
 * @method static File|File[]|null get(...$args)
 * @method static File|null getById(?string $id, ?string $user_id, PDO $pdo, bool $dangerouslyDisregardUserId = false)
 * @method static File|File[]|null getByQuery(array $params = [], string $whereClause = null, string $groupByClause = null, string $orderByClause = null, string $user_id, PDO $pdo)
 * @method static File|null delete(string $id, ?string $user_id, PDO $pdo, bool $dangerouslyDisregardUserId = false)
 */
class File extends AbstractObject
{
    use ScalarToBoolean;

    public const QUEUE_ID = 'queue_id';
    public const FILENAME = 'filename';
    public const PATH = 'path';
    public const TAGS = 'tags';
    public const COMMENT = 'comment';
    public const QUEUED = 'queued';
    public const AVAILABLE = 'available';
    public const DEQUEUED = 'dequeued';
    public const FILESIZE = 'filesize';

    /** @var string */
    protected $queue_id;

    /** @var string */
    protected $filename;

    /** @var string */
    protected $path;

    /** @var string[] */
    protected $tags;

    /** @var string|null */
    protected $comment;

    /** @var bool */
    protected $queued;

    /** @var bool */
    protected $available;

    /** @var DateTimeImmutable | string */
    protected $dequeued;

    /** @var string|null not stored in DB, should no tbe written to! */
    protected $filesize;

    /** @var bool */
    private $exists;

    /**
     * File constructor.
     * @param array $data
     * @param callable|null $filter
     * @param PDO|null $pdo
     * @throws Exception
     */
    public function __construct(array $data, callable $filter = null, PDO $pdo = null)
    {
        parent::__construct($data, function ($property, $value) use ($filter) {
            switch ($property) {
                case static::FILESIZE:
                    throw new Exception('Filesize property is not manually adjustable');
                case static::TAGS:
                    if ($value) {
                        return array_map('trim', explode(',', $value));
                    } else {
                        return null;
                    }
                case static::QUEUED:
                case static::AVAILABLE:
                    return self::scalarToBoolean($value);
                /** @noinspection PhpMissingBreakStatementInspection */
                case static::DEQUEUED:
                    if ($value) {
                        try {
                            return new DateTimeImmutable($value);
                        } catch (Exception $e) {
                            // fall-through to default behavior
                        }
                    } else {
                        return null;
                    }
                default:
                    if ($filter && is_callable($filter)) {
                        return $filter($property, $value);
                    }
                    return $value;
            }
        }, $pdo);

        // check if the file is _really_ available (and update as appropriate)
        $exists = file_exists($this->getPath());
        if ($exists != $this->available) {
            $this->update([static::AVAILABLE => $exists]);
        }
    }

    /**
     * @param array|int[]|string[] $filter
     * @param string|null $user_id
     * @param PDO $pdo
     * @param bool $dangerouslyDisregardUserId
     * @return File[]
     */
    public static function getByFilter(array $filter, ?string $user_id, PDO $pdo, bool $dangerouslyDisregardUserId = false): array
    {
        if (isset($filter[static::QUEUED])) {
            $filter[static::QUEUED] = self::booleanToMySQLBoolean($filter[static::QUEUED]);
        }
        if (isset($filter[static::AVAILABLE])) {
            $filter[static::AVAILABLE] = self::booleanToMySQLBoolean($filter[static::AVAILABLE]);
        }
        $result =  parent::getByFilter($filter, $user_id, $pdo, $dangerouslyDisregardUserId);

        // don't return the files that discovered they were unavailable on this query, if we specified a level of
        // availability in the filter
        return array_filter($result, function (File $file) use ($filter) {
            return !$filter[static::AVAILABLE] || $filter[static::AVAILABLE] == $file->available;
        });
    }

    public function update(array $data, bool $dangerouslyDisregardUserId = false)
    {
        if (isset($data[File::QUEUED])) {
            $data[File::QUEUED] = self::booleanToMySQLBoolean($data[File::QUEUED]);
        }
        if (isset($data[File::AVAILABLE])) {
            $data[File::AVAILABLE] = self::booleanToMySQLBoolean($data[File::AVAILABLE]);
        }
        parent::update($data, $dangerouslyDisregardUserId);
    }

    /**
     * @param array $include
     * @param array $exclude
     * @param array $overrides
     * @param bool $dangerouslyDisregardUserId
     * @return array
     * @throws Exception
     *
     * FIXME get rid of order field... or use it?
     */
    public function toArray(array $include = [], array $exclude = [], array $overrides = [], bool $dangerouslyDisregardUserId = false): array
    {
        return parent::toArray(
            $include,
            array_merge($exclude, [static::PATH, static::ORDER]),
            array_merge($overrides, [
                static::FILESIZE => $this->getFilesize(),
                static::DEQUEUED => $this->dequeued ? $this->getDequeued()->format(DateTimeImmutable::ISO8601) : null,
                static::QUEUED => $this->isQueued(),
                static::AVAILABLE => $this->isAvailable()
            ]),
            $dangerouslyDisregardUserId
        );
    }

    /**
     * @return string
     */
    public function getQueueId(): string
    {
        return $this->queue_id;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @return bool
     */
    public function isQueued(): bool
    {
        return $this->queued;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getDequeued(): ?DateTimeImmutable
    {
        return $this->dequeued;
    }

    public function getFilesize(): ?string
    {
        if ($this->isAvailable()) {
            $size = filesize($this->getPath());
            if ($size) {
                $units = [' bytes', 'KB', 'MB', 'GB'];
                for ($u = 0; $u < count($units) && $size > 1024; $u++) {
                    $size /= 1024;
                }
                return sprintf('%0.00d%s', $size, $units[$u]);
            }
        }
        return null;
    }
}
