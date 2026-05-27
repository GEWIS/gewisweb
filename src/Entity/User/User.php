<?php

declare(strict_types=1);

namespace App\Entity\User;

use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;
use App\Entity\Decision\Enums\MembershipTypes;
use App\Entity\Decision\Member as MemberModel;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\Enums\UserTypes;
use App\Entity\User\Traits\BackupCodeAwareTrait;
use App\Repository\User\UserRepository;
use App\Security\User\MfaEnforcementSwitch;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Override;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function in_array;

/**
 * User model.
 *
 * @psalm-import-type UserRoleGdprArrayType from UserRole as ImportedUserRoleGdprArrayType
 * @psalm-type UserGdprArrayType = array{
 *     roles: ImportedUserRoleGdprArrayType[],
 *     passwordChangedOn: ?string,
 * }
 */
#[Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    use BackupCodeAwareTrait;

    /**
     * The membership number.
     */
    #[Id]
    #[Column(type: Types::INTEGER)]
    private int $lidnr;

    /**
     * The user's password.
     */
    #[Column(type: Types::STRING)]
    private string $password;

    /**
     * The corresponding member for this user.
     */
    #[OneToOne(
        targetEntity: MemberModel::class,
        fetch: 'EAGER',
    )]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    private MemberModel $member;

    /**
     * User roles.
     *
     * @var Collection<array-key, UserRole>
     */
    #[OneToMany(
        targetEntity: UserRole::class,
        mappedBy: 'lidnr',
        fetch: 'EAGER',
    )]
    private Collection $roles;

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

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    /**
     * Return the `lidnr` of this user, generalised to `id`.
     */
    public function getId(): int
    {
        return $this->getLidnr();
    }

    /**
     * Get the membership number.
     */
    public function getLidnr(): int
    {
        return $this->lidnr;
    }

    public function setLidnr(int $lidnr): void
    {
        $this->lidnr = $lidnr;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    #[Override]
    public function getUserIdentifier(): string
    {
        return (string) $this->lidnr;
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
     * @see UserInterface
     *
     * @psalm-return array<array-key, value-of<UserRoles>|string>
     */
    #[Override]
    public function getRoles(): array
    {
        $member = $this->getMember();
        if (MembershipTypes::Graduate === $member->getType()) {
            $baseRole = UserRoles::Graduate->value;
        } elseif ($member->isActive()) {
            $baseRole = UserRoles::ActiveMember->value;
        } else {
            $baseRole = UserRoles::Member->value;
        }

        $explicitRoles = array_map(
            static fn (UserRole $role) => $role->getRole()->value,
            $this->roles->filter(static fn (UserRole $role): bool => $role->isActive())->getValues(),
        );

        $roles = [
            $baseRole,
            $member->getSelfRole(),
            ...$explicitRoles,
        ];

        // ROLE_BOARD is granted dynamically based on current board installations, mirroring how the membership-based
        // roles above are derived from `Member` state rather than `UserRole` rows.
        if ($member->isBoardMember()) {
            $roles[] = UserRoles::Board->value;
        }

        // When MFA enforcement is on and this user is in scope (admin or current board member) but has not enrolled,
        // strip `ROLE_ADMIN` and `ROLE_BOARD` from the returned set so every existing `IsGranted` / `access_control`
        // check fails. The exception is then converted into a redirect to enrolment by `MfaEnforcementListener`.
        if (
            MfaEnforcementSwitch::isEnabled()
            && null === $this->totpSecret
            && $this->isMfaRequiredScope(
                $member,
                $explicitRoles,
            )
        ) {
            $roles = array_values(array_filter(
                $roles,
                static fn (string $role): bool => UserRoles::Admin->value !== $role
                    && UserRoles::Board->value !== $role,
            ));
        }

        return array_unique($roles);
    }

    /**
     * @return Collection<array-key, UserRole>
     */
    public function getRoleEntities(): Collection
    {
        return $this->roles;
    }

    /**
     * @param string[] $explicitRoles
     */
    private function isMfaRequiredScope(
        MemberModel $member,
        array $explicitRoles,
    ): bool {
        if ($member->isBoardMember()) {
            return true;
        }

        return in_array(
            UserRoles::Admin->value,
            $explicitRoles,
            true,
        );
    }

    /**
     * Get the member information of this user.
     */
    public function getMember(): MemberModel
    {
        return $this->member;
    }

    public function setMember(MemberModel $member): void
    {
        $this->member = $member;
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
        return UserTypes::User;
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
