<?php

namespace Photo\Model;

use DateTime;
use Decision\Model\Member;
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
     * @ORM\Column(type="datetime")
     */
    protected $dateTime;

    /**
     * Date and time when the photo was taken.
     *
     * @ORM\Column(type="boolean")
     */
    protected $explicit;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Photo
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * @return Member
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * Get the date.
     *
     * @return DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * Get the explicit bool
     *
     * @return bool
     */
    public function isExplicit()
    {
        return $this->explicit;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param Photo $photo
     */
    public function setPhoto($photo)
    {
        $this->photo = $photo;
    }

    /**
     * @param Member $member
     */
    public function setMember($member)
    {
        $this->member = $member;
    }

    /**
     * @param DateTime
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @param bool
     */
    public function setExplicit($explicit)
    {
        $this->explicit = $explicit;
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
