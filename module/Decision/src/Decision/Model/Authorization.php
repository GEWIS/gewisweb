<?php

namespace Decision\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Authorization model.
 *
 * @ORM\Entity
 * @ORM\Table(name="Authorization",uniqueConstraints={@ORM\UniqueConstraint(name="auth_idx", columns={"authorizer", "recipient", "meetingNumber"})})
 */
class Authorization
{

    /**
     * Authorization ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Member submitting this authorization.
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Member"))
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="authorizer")
     */
    protected $authorizer;

    /**
     * Member receiving this authorization..
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Member"))
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="recipient")
     */
    protected $recipient;

    /**
     * Meeting number
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $meetingNumber;

    /**
     * Has the authorization been revoked?
     *
     * @ORM\Column(type="boolean"))
     */
    protected $revoked = false;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Decision\Model\Member
     */
    public function getAuthorizer()
    {
        return $this->authorizer;
    }

    /**
     * @param \Decision\Model\Member $authorizer
     */
    public function setAuthorizer($authorizer)
    {
        $this->authorizer = $authorizer;
    }

    /**
     * @return \Decision\Model\Member
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @param \Decision\Model\Member $recipient
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * @return integer
     */
    public function getMeetingNumber()
    {
        return $this->meetingNumber;
    }

    /**
     * @param integer $meetingNumber
     */
    public function setMeetingNumber($meetingNumber)
    {
        $this->meetingNumber = $meetingNumber;
    }

    /**
     * @return bool
     */
    public function getRevoked()
    {
        return $this->revoked;
    }

    /**
     * @param bool $revoked
     */
    public function setRevoked($revoked)
    {
        $this->revoked = $revoked;
    }
}
