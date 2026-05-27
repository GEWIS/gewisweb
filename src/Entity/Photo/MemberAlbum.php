<?php

declare(strict_types=1);

namespace App\Entity\Photo;

use App\Entity\Decision\Member as MemberModel;

/**
 * Contains all photos with tags of a member.
 * This is a VirtualAlbum, meaning that it is not persisted.
 */
class MemberAlbum extends VirtualAlbum
{
    public function __construct(
        int $id,
        private readonly MemberModel $member,
    ) {
        parent::__construct($id);
    }

    public function getMember(): MemberModel
    {
        return $this->member;
    }
}
