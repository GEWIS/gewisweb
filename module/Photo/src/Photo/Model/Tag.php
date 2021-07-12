<?php

namespace Photo\Model;

use Decision\Model\Member;
use Doctrine\ORM\Mapping as ORM;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Tag.
 *
 * @ORM\Entity
 * @ORM\Table(name="Tag",uniqueConstraints={@ORM\UniqueConstraint(name="tag_idx", columns={"photo_id", "member_id"})})
 */
class Tag implements ResourceInterface
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
     * Returns the Tag as an associative array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'photo_id' => $this->getPhoto()->getId(),
            'member_id' => $this->getMember()->getLidnr()
        ];
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'tag';
    }
}
