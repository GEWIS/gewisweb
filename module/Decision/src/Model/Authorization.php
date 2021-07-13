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
     * @ORM\ManyToOne(targetEntity="Member"))
     * @ORM\JoinColumn(name="authorizer", referencedColumnName="lidnr")
     */
    protected $authorizer;

    /**
     * Member receiving this authorization..
     *
     * @ORM\ManyToOne(targetEntity="Member"))
     * @ORM\JoinColumn(name="recipient", referencedColumnName="lidnr")
     */
    protected $recipient;

    /**
     * Meeting number.
     *
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Member
     */
    public function getAuthorizer()
    {
        return $this->authorizer;
    }

    /**
     * @param Member $authorizer
     */
    public function setAuthorizer($authorizer)
    {
        $this->authorizer = $authorizer;
    }

    /**
     * @return Member
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @param Member $recipient
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * @return int
     */
    public function getMeetingNumber()
    {
        return $this->meetingNumber;
    }

    /**
     * @param int $meetingNumber
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
