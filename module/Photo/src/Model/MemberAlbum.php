<?php

namespace Photo\Model;

use Decision\Model\Member as MemberModel;

/**
 * Class MemberAlbum
 * Contains all photos with tags of a member.
 * This is a VirtualAlbum, meaning that it is not persisted.
 */
class MemberAlbum extends VirtualAlbum
{
    /**
     * Member that this album is for.
     *
     * @var MemberModel
     */
    private MemberModel $member;

    /**
     * MemberAlbum constructor.
     *
     * @param int $id it is best to use the member lidnr here
     * @param MemberModel $member
     */
    public function __construct(int $id, MemberModel $member)
    {
        parent::__construct($id);
        $this->member = $member;
    }

    public function getMember(): MemberModel
    {
        return $this->member;
    }
}
