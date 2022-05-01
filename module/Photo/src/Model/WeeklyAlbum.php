<?php

namespace Photo\Model;

/**
 * Contains all photos of the week in a certain year. This is a VirtualAlbum, meaning that it is not persisted.
 */
class WeeklyAlbum extends VirtualAlbum
{
    /**
     * The dates for each photo of the week in this year.
     */
    private array $dates;

    /**
     * MemberAlbum constructor.
     *
     * @param int $id
     * @param array $dates
     */
    public function __construct(
        int $id,
        array $dates,
    ) {
        parent::__construct($id);
        $this->dates = $dates;
    }

    public function getDates(): array
    {
        return $this->dates;
    }
}
