<?php

declare(strict_types=1);

namespace Photo\Model;

use DateTime;

/**
 * Contains all photos of the week in a certain year. This is a VirtualAlbum, meaning that it is not persisted.
 */
class WeeklyAlbum extends VirtualAlbum
{
    /**
     * @param DateTime[] $dates
     */
    public function __construct(
        int $id,
        private readonly array $dates,
    ) {
        parent::__construct($id);
    }

    /**
     * @return DateTime[]
     */
    public function getDates(): array
    {
        return $this->dates;
    }
}
