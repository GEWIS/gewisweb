<?php

namespace Photo\Model;

/**
 * Contains all photos of the week in a certain year. This is a VirtualAlbum, meaning that it is not persisted.
 */
class WeeklyAlbum extends VirtualAlbum
{
    public function __construct(
        int $id,
        private readonly array $dates,
    ) {
        parent::__construct($id);
    }

    public function getDates(): array
    {
        return $this->dates;
    }
}
