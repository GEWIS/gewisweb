<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Application\Traits\SelectorTokenTrait;
use App\Entity\Decision\Member;
use App\Entity\User\Enums\UserTypes;
use App\Repository\User\PasswordResetRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use InvalidArgumentException;

#[Entity(repositoryClass: PasswordResetRepository::class)]
#[Index(
    columns: ['selector'],
    name: 'IDX_password_reset_selector',
)]
#[Index(
    columns: ['tempHash'],
    name: 'IDX_password_reset_temp_hash',
)]
class PasswordReset
{
    use SelectorTokenTrait;

    #[Id]
    #[GeneratedValue]
    #[Column]
    private ?int $id = null;

    #[Column(
        type: Types::STRING,
        enumType: UserTypes::class,
    )]
    private UserTypes $userType;

    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    private ?Member $member = null;

    #[ManyToOne(targetEntity: CompanyUser::class)]
    #[JoinColumn(nullable: true)]
    private ?CompanyUser $companyUser = null;

    /**
     * Ephemeral hash linking the email-link click (stage 1) to the form-render request (stage 2). Cleared on first
     * successful stage-2 read to enforce single-use. The original token never appears in the stage-2 URL.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    protected ?string $tempHash = null;

    #[Column(
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
    )]
    protected ?DateTimeImmutable $tempHashExpiresAt = null;

    public function __construct(
        DateTimeImmutable $expiresAt,
        string $selector,
        string $hashedToken,
        ?Member $member = null,
        ?CompanyUser $companyUser = null,
    ) {
        if (
            null === $member
            && null === $companyUser
        ) {
            throw new InvalidArgumentException('Either $member or $companyUser must be provided');
        }

        if (
            null !== $member
            && null !== $companyUser
        ) {
            throw new InvalidArgumentException('Only one of $member or $companyUser should be provided');
        }

        $this->expiresAt = $expiresAt;
        $this->selector = $selector;
        $this->hashedToken = $hashedToken;
        $this->member = $member;
        $this->companyUser = $companyUser;
        $this->userType = null !== $member
            ? UserTypes::User
            : UserTypes::CompanyUser;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserType(): UserTypes
    {
        return $this->userType;
    }

    public function getMember(): ?Member
    {
        return $this->member;
    }

    public function getCompanyUser(): ?CompanyUser
    {
        return $this->companyUser;
    }

    public function getTempHash(): ?string
    {
        return $this->tempHash;
    }

    public function setTempHash(?string $tempHash): void
    {
        $this->tempHash = $tempHash;
    }

    public function getTempHashExpiresAt(): ?DateTimeImmutable
    {
        return $this->tempHashExpiresAt;
    }

    public function setTempHashExpiresAt(?DateTimeImmutable $tempHashExpiresAt): void
    {
        $this->tempHashExpiresAt = $tempHashExpiresAt;
    }

    public function isTempHashExpired(): bool
    {
        return null === $this->tempHashExpiresAt
            || $this->tempHashExpiresAt <= new DateTimeImmutable('now');
    }
}
