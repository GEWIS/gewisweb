<?php

declare(strict_types=1);

namespace App\Entity\Photo;

use App\Entity\Decision\Organ;

/**
 * Contains all photos tagged with an organ.
 * This is a VirtualAlbum, meaning that it is not persisted.
 */
class OrganAlbum extends VirtualAlbum
{
    public function __construct(
        int $id,
        private readonly Organ $organ,
    ) {
        parent::__construct($id);
    }

    public function getOrgan(): Organ
    {
        return $this->organ;
    }
}
