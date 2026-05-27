<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\User\Enums\UserRoles;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * User role model.
 *
 * This specifies all the roles of a user.
 *
 * @psalm-type UserRoleGdprArrayType = array{
 *     role: value-of<UserRoles>,
 *     expiration: ?string,
 * }
 */
#[Entity]
class UserRole
{
    use IdentifiableTrait;

    /**
     * The membership number of the user with this role.
     */
    #[ManyToOne(
        targetEntity: User::class,
        inversedBy: 'roles',
    )]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    private User $lidnr;

    /**
     * The user's role.
     */
    #[Column(
        type: Types::STRING,
        enumType: UserRoles::class,
    )]
    private UserRoles $role;

    /**
     * Date after which this role has expired.
     */
    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $expiration = null;

    /**
     * Get the membership number.
     */
    public function getLidnr(): User
    {
        return $this->lidnr;
    }

    /**
     * Set the membership number.
     */
    public function setLidnr(User $lidnr): void
    {
        $this->lidnr = $lidnr;
    }

    /**
     * Get the role.
     */
    public function getRole(): UserRoles
    {
        return $this->role;
    }

    /**
     * Set the role.
     */
    public function setRole(UserRoles $role): void
    {
        $this->role = $role;
    }

    /**
     * Get the expiration, `null` means invalid (and thus inactive).
     */
    public function getExpiration(): ?DateTime
    {
        return $this->expiration;
    }

    /**
     * Set the expiration date.
     */
    public function setExpiration(DateTime $expiration): void
    {
        $this->expiration = $expiration;
    }

    /**
     * Determine whether this role is active (i.e. has not expired).
     */
    public function isActive(): bool
    {
        return null !== $this->expiration
            && (new DateTime('now')) < $this->expiration;
    }

    /**
     * @return UserRoleGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'role' => $this->getRole()->value,
            'expiration' => $this->getExpiration()?->format(DateTimeInterface::ATOM),
        ];
    }
}
