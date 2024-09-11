<?php

declare(strict_types=1);

namespace Application\Model\Traits;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;

/**
 * A trait which provides basic (repeated) functionality for proposed update entities.
 *
 * TODO: Make activities also use this trait.
 *
 * @template T of object
 */
trait UpdateProposableTrait
{
    /**
     * Whether this entity is a proposed update for another entity.
     */
    #[Column(type: 'boolean')]
    protected bool $isUpdate = false;

    /**
     * Get whether this is a proposed update.
     */
    public function getIsUpdate(): bool
    {
        return $this->isUpdate;
    }

    /**
     * Get whether this is a proposed update.
     */
    public function isUpdate(): bool
    {
        return $this->isUpdate;
    }

    /**
     * Set whether this is a proposed update.
     */
    public function setIsUpdate(bool $isUpdate): void
    {
        $this->isUpdate = $isUpdate;
    }

    /**
     * Get update proposals for this entity.
     *
     * @psalm-return Collection<array-key, T>
     */
    abstract public function getUpdateProposals(): Collection;
}
