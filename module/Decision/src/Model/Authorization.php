<?php

namespace Decision\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
    UniqueConstraint,
};

/**
 * Authorization model.
 */
#[Entity]
#[UniqueConstraint(
    name: "auth_idx",
    columns: ["authorizer", "recipient", "meetingNumber"],
)]
class Authorization
{
    /**
     * Authorization ID.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     * Member submitting this authorization.
     */
    #[ManyToOne(targetEntity: "Decision\Model\Member")]
    #[JoinColumn(
        name: "authorizer",
        referencedColumnName: "lidnr",
    )]
    protected Member $authorizer;

    /**
     * Member receiving this authorization..
     */
    #[ManyToOne(targetEntity: "Decision\Model\Member")]
    #[JoinColumn(
        name: "recipient",
        referencedColumnName: "lidnr",
    )]
    protected Member $recipient;

    /**
     * Meeting number.
     */
    #[Column(type: "integer")]
    protected int $meetingNumber;

    /**
     * Has the authorization been revoked?
     */
    #[Column(type: "boolean")]
    protected bool $revoked = false;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Member
     */
    public function getAuthorizer(): Member
    {
        return $this->authorizer;
    }

    /**
     * @param Member $authorizer
     */
    public function setAuthorizer(Member $authorizer): void
    {
        $this->authorizer = $authorizer;
    }

    /**
     * @return Member
     */
    public function getRecipient(): Member
    {
        return $this->recipient;
    }

    /**
     * @param Member $recipient
     */
    public function setRecipient(Member $recipient): void
    {
        $this->recipient = $recipient;
    }

    /**
     * @return int
     */
    public function getMeetingNumber(): int
    {
        return $this->meetingNumber;
    }

    /**
     * @param int $meetingNumber
     */
    public function setMeetingNumber(int $meetingNumber): void
    {
        $this->meetingNumber = $meetingNumber;
    }

    /**
     * @return bool
     */
    public function getRevoked(): bool
    {
        return $this->revoked;
    }

    /**
     * @param bool $revoked
     */
    public function setRevoked(bool $revoked): void
    {
        $this->revoked = $revoked;
    }
}
