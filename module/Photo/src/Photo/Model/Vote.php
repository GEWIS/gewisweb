<?php

namespace Photo\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Vote, represents a vote for a photo of the week.
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 */
class Vote implements ResourceInterface
{

    /**
     * Vote ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Date and time when the photo was voted for.
     *
     * @ORM\Column(type="datetime")
     */
    protected $dateTime;

    /**
     * The photo which was viewed.
     *
     * @ORM\ManyToOne(targetEntity="Photo\Model\Photo", inversedBy="hits")
     * @ORM\JoinColumn(name="photo_id", referencedColumnName="id")
     */
    protected $photo;

    /**
     * @ORM\ManyToOne(targetEntity="Decision\Model\Member")
     * @ORM\JoinColumn(name="member_id",referencedColumnName="lidnr")
     */
    protected $member;


    /**
     * Vote constructor.
     * @param \Decision\Model\Member $member The member whom voted
     */
    public function __construct($photo, $member)
    {
        $this->dateTime = new \DateTime();
        $this->member = $member;
        $this->photo = $photo;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'vote';
    }
}
