<?php

declare(strict_types=1);

namespace App\Entity\User;

use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;
use App\Entity\Career\Company as CompanyModel;
use App\Entity\User\Enums\UserTypes;
use App\Entity\User\Traits\BackupCodeAwareTrait;
use App\Repository\User\CompanyUserRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Override;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use function sprintf;

#[Entity(repositoryClass: CompanyUserRepository::class)]
class CompanyUser implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    use BackupCodeAwareTrait;

    /**
     * The internal identifier for this company.
     */
    #[Id]
    #[Column(type: Types::INTEGER)]
    private int $id;

    /**
     * The company's password.
     */
    #[Column(type: Types::STRING)]
    private string $password;

    /**
     * The company for this company user.
     */
    #[OneToOne(
        targetEntity: CompanyModel::class,
        fetch: 'EAGER',
    )]
    #[JoinColumn(
        name: 'id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private CompanyModel $company;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    #[Override]
    public function getUserIdentifier(): string
    {
        return (string) $this->id;
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

    /**
     * Get the company.
     */
    public function getCompany(): CompanyModel
    {
        return $this->company;
    }

    /**
     * A human-readable name for this account, for display alongside a {@see User}: the company name with the
     * representative who acts for it in parentheses.
     */
    public function getDisplayName(): string
    {
        return sprintf(
            '%s (%s)',
            $this->getCompany()->getName(),
            $this->getCompany()->getRepresentativeName(),
        );
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
        return (string) $this->id;
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
