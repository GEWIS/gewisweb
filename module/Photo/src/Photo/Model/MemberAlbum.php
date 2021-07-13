<?php

namespace Photo\Model;

use Decision\Model\Member;

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
     * @var Member
     */
    private $member;

    /**
     * MemberAlbum constructor.
     *
     * @param int $id it is best to use the member lidnr here
     */
    public function __construct($id, Member $member)
    {
        parent::__construct($id);
        $this->member = $member;
    }

    public function getMember()
    {
        return $this->member;
    }
}
