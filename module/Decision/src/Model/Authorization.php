<?php

namespace Decision\Model;

use DateTime;
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
    columns: ["authorizer", "recipient", "meetingNumber", "revokedAt"],
)]
class Authorization
{
    /**
     * Authorization ID.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * Member submitting this authorization.
     */
    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        name: "authorizer",
        referencedColumnName: "lidnr",
    )]
    protected Member $authorizer;

    /**
     * Member receiving this authorization..
     */
    #[ManyToOne(targetEntity: Member::class)]
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
     * When the authorization was made.
     */
    #[Column(type: "datetime")]
    protected DateTime $createdAt;

    /**
     * When the authorization was revoked.
     */
    #[Column(
        type: "datetime",
        nullable: true,
    )]
    protected ?DateTime $revokedAt = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
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

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getRevokedAt(): ?DateTime
    {
        return $this->revokedAt;
    }

    public function setRevokedAt(?DateTime $revokedAt): void
    {
        $this->revokedAt = $revokedAt;
    }
}
