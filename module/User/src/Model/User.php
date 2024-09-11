<?php

declare(strict_types=1);

namespace User\Model;

use Application\Model\IdentityInterface;
use DateTime;
use DateTimeInterface;
use Decision\Model\Enums\MembershipTypes;
use Decision\Model\Member as MemberModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use RuntimeException;
use User\Model\Enums\UserRoles;

use function count;
use function in_array;
use function sprintf;

/**
 * User model.
 *
 * @psalm-import-type UserRoleGdprArrayType from UserRole as ImportedUserRoleGdprArrayType
 * @psalm-type UserGdprArrayType = array{
 *     roles: ImportedUserRoleGdprArrayType[],
 *     passwordChangedOn: ?string,
 * }
 */
#[Entity]
class User implements IdentityInterface
{
    /**
     * The membership number.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $lidnr;

    /**
     * The user's password.
     */
    #[Column(type: 'string')]
    protected string $password;

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
    protected Collection $roles;

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
    protected MemberModel $member;

    /**
     * Timestamp when the password was last changed.
     */
    #[Column(
        type: 'datetime',
        nullable: true,
    )]
    protected ?DateTime $passwordChangedOn = null;

    public function __construct(?NewUser $newUser = null)
    {
        $this->roles = new ArrayCollection();

        if (null === $newUser) {
            return;
        }

        $this->lidnr = $newUser->getLidnr();
        $this->member = $newUser->getMember();
    }

    /**
     * Return the `lidnr` of this user, generalised to `id` for the {@link AuthenticationService}.
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

    /**
     * Get the user's email address.
     */
    public function getEmail(): ?string
    {
        return $this->member->getEmail();
    }

    /**
     * Get the password hash.
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set the password hash.
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * Get the user's roles.
     *
     * @return Collection<array-key, UserRole>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * Get the member information of this user.
     */
    public function getMember(): MemberModel
    {
        return $this->member;
    }

    /**
     * Get the user's active role names.
     *
     * @return string[] Role names
     */
    public function getRoleNames(): array
    {
        $names = [];

        foreach ($this->getRoles() as $role) {
            if (!$role->isActive()) {
                continue;
            }

            $names[] = $role->getRole()->value;
        }

        return $names;
    }

    public function getRoleId(): string
    {
        $roleNames = $this->getRoleNames();
        if (in_array('admin', $roleNames)) {
            return UserRoles::Admin->value;
        }

        if ($this->getMember()->isBoardMember()) {
            return UserRoles::Board->value;
        }

        if (in_array('company_admin', $roleNames)) {
            return UserRoles::CompanyAdmin->value;
        }

        if (empty($roleNames)) {
            if (MembershipTypes::Graduate === $this->getMember()->getType()) {
                return UserRoles::Graduate->value;
            }

            if (count($this->getMember()->getCurrentOrganInstallations()) > 0) {
                return UserRoles::ActiveMember->value;
            }

            return UserRoles::User->value;
        }

        throw new RuntimeException(
            sprintf('Could not determine user role unambiguously for user %s', $this->getLidnr()),
        );
    }

    public function setRoles(ArrayCollection $roles): void
    {
        $this->roles = $roles;
    }

    public function setLidnr(int $lidnr): void
    {
        $this->lidnr = $lidnr;
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

    /**
     * @return UserGdprArrayType
     */
    public function toGdprArray(): array
    {
        /** @var ImportedUserRoleGdprArrayType[] $roles */
        $roles = [];
        foreach ($this->getRoles() as $role) {
            $roles[] = $role->toGdprArray();
        }

        return [
            'roles' => $roles,
            'passwordChangedOn' => $this->getPasswordChangedOn()?->format(DateTimeInterface::ATOM),
        ];
    }

    /**
     * Get the user's resource ID.
     */
    public function getResourceId(): string
    {
        return 'user';
    }
}
