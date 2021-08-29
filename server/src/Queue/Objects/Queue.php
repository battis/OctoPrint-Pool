<?php


namespace Battis\OctoPrintPool\Queue\Objects;


use Battis\OctoPrintPool\Queue\Strategies\Filenaming\AbstractFilenamingStrategy;
use Battis\WebApp\Server\API\Objects\AbstractObject;
use Battis\WebApp\Server\Traits\ScalarToBoolean;
use Exception;
use PDO;

/**
 * @method static Queue|Queue[]|null get(mixed ...$args)
 * @method static Queue getById(?string $id, ?string $user_id, PDO $pdo, bool $dangerouslyDisregardUserId = false)
 * @method static Queue[] getByFilter(array $filter, ?string $user_id, PDO $pdo, bool $dangerouslyDisregardUserId = false)
 * @method static Queue|Queue[]|null getByQuery(array $params = [], string $whereClause = null, string $groupByClause = null, string $orderByClause = null, string $user_id, PDO $pdo)
 * @method static Queue|null delete(string $id, string $user_id, PDO $pdo, bool $dangerouslyDisregardUserId = false)
 */
class Queue extends AbstractObject
{
    use ScalarToBoolean;

    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const COMMENT = 'comment';
    public const ROOT = 'root';
    public const FILENAMING_STRATEGY = 'filenaming_strategy';
    public const FILENAME_PATTERN = 'filename_pattern';
    public const CLEANUP_STRATEGY = 'cleanup_strategy';
    public const CLEANUP_PARAMS = 'cleanup_params';
    public const MANAGEABLE = 'manageable';

    /** @var string */
    protected $name;

    /** @var string|null */
    protected $description;

    /** @var string|null */
    protected $comment;

    /** @var string|null */
    protected $root;

    /** @var string|null */
    protected $filenaming_strategy;

    /** @var string|null */
    protected $filename_pattern;

    /** @var string|null */
    protected $cleanup_strategy;

    protected $cleanup_params;

    /** @var bool */
    protected $manageable;

    public function __construct(array $data, callable $filter = null, PDO $pdo = null)
    {
        parent::__construct($data, function($property, $value) use ($filter) {
            switch($property) {
                case static::MANAGEABLE:
                    return self::scalarToBoolean($value);
                case static::CLEANUP_PARAMS:
                    return json_decode($value, true);
                default:
                    if ($filter && is_callable($filter)) {
                        return $filter($property, $value);
                    }
                    return $value;
            }
        }, $pdo);
    }

    /**
     * @param array $data
     * @param string|null $user_id
     * @param PDO $pdo
     * @param bool $dangerouslyDisregardUserId
     * @return Queue|null
     */
    public static function insert(array $data, ?string $user_id, PDO $pdo, bool $dangerouslyDisregardUserId = false): ?AbstractObject
    {
        if (isset($data[static::CLEANUP_PARAMS]) && !is_string($data[static::CLEANUP_PARAMS])) {
            $data[static::CLEANUP_PARAMS] = json_encode($data[static::CLEANUP_PARAMS]);
        }
        return parent::insert($data, $user_id, $pdo, $dangerouslyDisregardUserId);
    }

    public function update(array $data, bool $dangerouslyDisregardUserId = false)
    {
        if (isset($data[static::CLEANUP_PARAMS]) && !is_string($data[static::CLEANUP_PARAMS])) {
            $data[static::CLEANUP_PARAMS] = json_encode($data[static::CLEANUP_PARAMS]);
        }
        parent::update($data, $dangerouslyDisregardUserId);
    }

    /**
     * @return File[]
     */
    public function getAvailableFiles(): array
    {
        return File::getByFilter([static::foreignKey() => $this->getId(), File::AVAILABLE => true], null, $this->pdo,
            true);
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
            array_merge($exclude, [static::ORDER]),
            [static::MANAGEABLE => $this->isManageable()],
            $dangerouslyDisregardUserId
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return AbstractFilenamingStrategy|null
     */
    public function getFilenamingStrategy(): ?string
    {
        return $this->filenaming_strategy;
    }

    /**
     * @return string|null
     */
    public function getCleanupStrategy(): ?string
    {
        return $this->cleanup_strategy;
    }

    public function getCleanupParams()
    {
        return $this->cleanup_params;
    }

    /**
     * @return string|null
     */
    public function getRoot(): ?string
    {
        return $this->root ?: $_ENV['VAR_PATH'] . "/{$this->getId()}";
    }

    /**
     * @return bool
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @return string|null
     */
    public function getFilenamePattern(): ?string
    {
        return $this->filename_pattern;
    }

    /**
     * @return bool
     */
    public function isManageable(): bool
    {
        return $this->manageable;
    }

}
