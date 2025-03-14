<?php

declare(strict_types=1);

namespace Photo\Model;

use Doctrine\Common\Collections\Collection;

/**
 * @template T
 */
interface TaggableInterface
{
    /**
     * @psalm-ignore-nullable-return
     */
    public function getId(): ?int;

    /**
     * @psalm-return Collection<array-key, T>
     */
    public function getTags(): Collection;
}
