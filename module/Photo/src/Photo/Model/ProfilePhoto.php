<?php

namespace Photo\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * ProfilePhoto.
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 */
class ProfilePhoto implements ResourceInterface
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
     * @ORM\OneToOne(targetEntity="Decision\Model\Member")
     * @ORM\JoinColumn(name="member_id",referencedColumnName="lidnr")
     */
    protected $member;

    /**
     * Date and time when the photo was taken.
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $dateTime;

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
     * Get the date.
     *
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
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
     * @param \DateTime
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;
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
