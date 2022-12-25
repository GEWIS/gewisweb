<?php

namespace Application\Model\Traits;

use Doctrine\ORM\Mapping\{
    Column,
    GeneratedValue,
    Id,
};

/**
 * A trait which provides an `id` column for entities.
 */
trait IdentifiableTrait
{
    /**
     * The default value must be `null` to prevent issues with auto generating the value. The column is strictly not
     * nullable.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "IDENTITY")]
    protected ?int $id = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Setting the identifier manually will, in most instances, result in undefined behaviour. Use with caution!
     *
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }
}
