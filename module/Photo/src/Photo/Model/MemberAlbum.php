<?php

namespace Photo\Model;

/**
 * Class MemberAlbum
 * Contains all photos with tags of a member.
 * This is a VirtualAlbum, meaning that it is not persisted.
 *
 * @package Photo\Model
 */
class MemberAlbum extends VirtualAlbum
{
    /**
     * Member that this album is for
     *
     * @var \Decision\Model\Member
     */
    private $member;
    
    /**
     * MemberAlbum constructor.
     *
     * @param int                    $id it is best to use the member lidnr here
     * @param \Decision\Model\Member $member
     */
    function __construct($id, \Decision\Model\Member $member)
    {
        parent::__construct($id);
        $this->member = $member;
    }
    
    public function getMember()
    {
        return $this->member;
    }
    
}
