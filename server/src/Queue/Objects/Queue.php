<?php


namespace Battis\OctoPrintPool\Queue\Objects;


use Battis\OctoPrintPool\Queue\Strategies\Filenaming\AbstractFilenamingStrategy;
use Battis\WebApp\Server\API\Objects\AbstractObject;
use Battis\WebApp\Server\Traits\ScalarToBoolean;
use Exception;

class Queue extends AbstractObject
{
    use ScalarToBoolean;

    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const COMMENT = 'comment';
    public const ROOT = 'root';
    public const FILENAMING_STRATEGY = 'filenaming_strategy';
    public const FILENAME_PATTERN = 'filename_pattern';
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

    /** @var bool */
    protected $manageable;

    public function __construct(array $data, callable $filter = null)
    {
        parent::__construct($data, function($property, $value) use ($filter) {
            switch($property) {
                case static::MANAGEABLE:
                    return self::scalarToBoolean($value);
                default:
                    if ($filter && is_callable($filter)) {
                        return $filter($property, $value);
                    }
                    return $value;
            }
        });
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
    public function getRoot(): ?string
    {
        return $this->root;
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
