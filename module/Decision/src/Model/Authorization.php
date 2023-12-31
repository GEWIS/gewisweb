<?php

declare(strict_types=1);

namespace Decision\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * Authorization model.
 *
 * @psalm-type AuthorizationGdprArrayType = array{
 *     meeting_number: int,
 *     createdAt: string,
 *     revokedAt: ?string,
 * }
 */
#[Entity]
#[UniqueConstraint(
    name: 'auth_idx',
    columns: ['authorizer', 'recipient', 'meetingNumber', 'revokedAt'],
)]
class Authorization
{
    use IdentifiableTrait;

    /**
     * Member submitting this authorization.
     */
    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        name: 'authorizer',
        referencedColumnName: 'lidnr',
    )]
    protected Member $authorizer;

    /**
     * Member receiving this authorization.
     */
    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        name: 'recipient',
        referencedColumnName: 'lidnr',
    )]
    protected Member $recipient;

    /**
     * Meeting number.
     */
    #[Column(type: 'integer')]
    protected int $meetingNumber;

    /**
     * When the authorization was made.
     */
    #[Column(type: 'datetime')]
    protected DateTime $createdAt;

    /**
     * When the authorization was revoked.
     */
    #[Column(
        type: 'datetime',
        nullable: true,
    )]
    protected ?DateTime $revokedAt = null;

    public function getAuthorizer(): Member
    {
        return $this->authorizer;
    }

    public function setAuthorizer(Member $authorizer): void
    {
        $this->authorizer = $authorizer;
    }

    public function getRecipient(): Member
    {
        return $this->recipient;
    }

    public function setRecipient(Member $recipient): void
    {
        $this->recipient = $recipient;
    }

    public function getMeetingNumber(): int
    {
        return $this->meetingNumber;
    }

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

    /**
     * @return AuthorizationGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'meeting_number' => $this->getMeetingNumber(),
            'createdAt' => $this->getCreatedAt()->format(DateTimeInterface::ATOM),
            'revokedAt' => $this->getRevokedAt()?->format(DateTimeInterface::ATOM),
        ];
    }
}
