<?php

declare(strict_types=1);

namespace App\Entity\User;

use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\Career\Company as CompanyModel;
use App\Entity\User\Enums\UserTypes;
use App\Entity\User\Traits\BackupCodeAwareTrait;
use App\Repository\User\CompanyUserRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Override;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use function assert;
use function sprintf;

/**
 * One of the people who represent a company in the careers portal. A company can have several; which one is the point
 * of contact for the board is recorded on the {@see CompanyModel} itself.
 */
#[Entity(repositoryClass: CompanyUserRepository::class)]
#[HasLifecycleCallbacks]
#[UniqueConstraint(
    name: 'company_user_email_uniq',
    columns: ['email'],
)]
class CompanyUser implements
    UserInterface,
    PasswordAuthenticatedUserInterface,
    TwoFactorInterface,
    BackupCodeAwareInterface
{
    use BackupCodeAwareTrait;
    use IdentifiableTrait;
    use TimestampableTrait;

    /**
     * The address this representative signs in with, and where their mail is sent.
     */
    #[Column(type: Types::STRING)]
    private string $email;

    /**
     * The representative's own name.
     */
    #[Column(type: Types::STRING)]
    private string $name;

    /**
     * The representative's password.
     */
    #[Column(type: Types::STRING)]
    private string $password;

    /**
     * The company this representative acts for.
     */
    #[ManyToOne(
        targetEntity: CompanyModel::class,
        fetch: 'EAGER',
    )]
    #[JoinColumn(
        nullable: false,
        onDelete: 'CASCADE',
    )]
    private CompanyModel $company;

    /**
     * When this representative was shut out, or null while they still act for the company. Someone who has moved on
     * keeps their row (the revisions and comments they left behind still point at it) but can no longer sign in; the
     * board removes the account outright once it is no longer worth keeping around.
     */
    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $disabledAt = null;

    /**
     * Timestamp when the password was last changed.
     */
    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $passwordChangedOn = null;

    /**
     * Timestamp after which remember-me logins must be refreshed.
     */
    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $forceReloginAt = null;

    /**
     * Base32-encoded TOTP shared secret. Null when TOTP MFA is disabled. Encrypted at rest via DoctrineEncryptBundle.
     */
    #[Column(
        type: Types::TEXT,
        nullable: true,
    )]
    #[Encrypted]
    private ?string $totpSecret = null;

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    #[Override]
    public function getUserIdentifier(): string
    {
        assert('' !== $this->email);

        return $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return string[]
     */
    #[Override]
    public function getRoles(): array
    {
        return ['ROLE_COMPANY_USER'];
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    #[Override]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the company.
     */
    public function getCompany(): CompanyModel
    {
        return $this->company;
    }

    public function setCompany(CompanyModel $company): void
    {
        $this->company = $company;
    }

    /**
     * A human-readable name for this account, for display alongside a {@see User}: the representative's name with the
     * company they act for in parentheses.
     */
    public function getDisplayName(): string
    {
        return sprintf(
            '%s (%s)',
            $this->getName(),
            $this->getCompany()->getName(),
        );
    }

    public function getDisabledAt(): ?DateTime
    {
        return $this->disabledAt;
    }

    public function setDisabledAt(?DateTime $disabledAt): void
    {
        $this->disabledAt = $disabledAt;
    }

    public function isDisabled(): bool
    {
        return null !== $this->disabledAt;
    }

    public function getPasswordChangedOn(): ?DateTime
    {
        return $this->passwordChangedOn;
    }

    public function setPasswordChangedOn(DateTime $passwordChangedOn): void
    {
        $this->passwordChangedOn = $passwordChangedOn;
    }

    public function getForceReloginAt(): ?DateTime
    {
        return $this->forceReloginAt;
    }

    public function setForceReloginAt(?DateTime $forceReloginAt): void
    {
        $this->forceReloginAt = $forceReloginAt;
    }

    public function getUserType(): UserTypes
    {
        return UserTypes::CompanyUser;
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $totpSecret): void
    {
        $this->totpSecret = $totpSecret;
    }

    #[Override]
    public function isTotpAuthenticationEnabled(): bool
    {
        return null !== $this->totpSecret;
    }

    #[Override]
    public function getTotpAuthenticationUsername(): string
    {
        return $this->getUserIdentifier();
    }

    #[Override]
    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        if (null === $this->totpSecret) {
            return null;
        }

        return new TotpConfiguration(
            $this->totpSecret,
            TotpConfiguration::ALGORITHM_SHA1,
            30,
            6,
        );
    }
}
