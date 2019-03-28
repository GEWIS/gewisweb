<?php

namespace Photo\Model;

use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * ProfilePhoto.
 *
 */
class ProfilePhoto
{

    /**
     * Tag ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Photo\Model\Photo", inversedBy="tags")
     * @ORM\JoinColumn(name="photo_id",referencedColumnName="id")
     */
    protected $photo;

    /**
     * @ORM\ManyToOne(targetEntity="Decision\Model\Member")
     * @ORM\JoinColumn(name="member_id",referencedColumnName="lidnr")
     */
    protected $member;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Photo\Model\Photo
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * @return \Decision\Model\Member
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param \Photo\Model\Photo $photo
     */
    public function setPhoto($photo)
    {
        $this->photo = $photo;
    }

    /**
     * @param \Decision\Model\Member $member
     */
    public function setMember($member)
    {
        $this->member = $member;
    }

    /**
     * Get the resource Id.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'profilePhoto';
    }

}
