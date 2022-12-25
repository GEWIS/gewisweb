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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
