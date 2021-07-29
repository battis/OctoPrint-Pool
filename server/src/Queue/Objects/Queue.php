<?php


namespace Battis\OctoPrintPool\Queue\Objects;


use Battis\OctoPrintPool\Queue\FileManagementStrategies\AbstractStrategy;
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
    public const FILE_MANAGEMENT_STRATEGY = 'file_management_strategy';

    /** @var string */
    protected $name;

    /** @var string|null */
    protected $description;

    /** @var string|null */
    protected $comment;

    /** @var string|null */
    protected $root;

    /** @var string|null */
    protected $file_management_strategy;

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
            $overrides,
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
     * @return AbstractStrategy|null
     */
    public function getFileManagementStrategy(): ?string
    {
        return $this->file_management_strategy;
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

}
