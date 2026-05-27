<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\Decision\AuthorizationRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
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
#[Entity(repositoryClass: AuthorizationRepository::class)]
#[UniqueConstraint(
    name: 'auth_idx',
    columns: [
        'authorizer',
        'recipient',
        'meetingNumber',
        'revokedAt',
    ],
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
    private Member $authorizer;

    /**
     * Member receiving this authorization.
     */
    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        name: 'recipient',
        referencedColumnName: 'lidnr',
    )]
    private Member $recipient;

    /**
     * Meeting number.
     */
    #[Column(type: Types::INTEGER)]
    private int $meetingNumber;

    /**
     * When the authorization was made.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $createdAt;

    /**
     * When the authorization was revoked.
     */
    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $revokedAt = null;

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
