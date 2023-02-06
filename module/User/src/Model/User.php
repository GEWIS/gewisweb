<?php

namespace User\Model;

use Application\Model\IdentityInterface;
use DateTime;
use Decision\Model\Enums\MembershipTypes;
use Decision\Model\Member as MemberModel;
use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{Column,
    Entity,
    Id,
    JoinColumn,
    OneToMany,
    OneToOne,
};
use RuntimeException;
use User\Authentication\AuthenticationService;

/**
 * User model.
 */
#[Entity]
class User implements IdentityInterface
{
    /**
     * The membership number.
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $lidnr;

    /**
     * The user's password.
     */
    #[Column(type: "string")]
    protected string $password;

    /**
     * User roles.
     */
    #[OneToMany(
        targetEntity: UserRole::class,
        mappedBy: "lidnr",
    )]
    protected Collection $roles;

    /**
     * The corresponding member for this user.
     */
    #[OneToOne(
        targetEntity: MemberModel::class,
        fetch: "EAGER",
    )]
    #[JoinColumn(
        name: "lidnr",
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected MemberModel $member;

    /**
     * Timestamp when the password was last changed.
     */
    #[Column(
        type: "datetime",
        nullable: true,
    )]
    protected ?DateTime $passwordChangedOn = null;

    // phpcs:ignore Gewis.General.RequireConstructorPromotion -- not possible
    public function __construct(NewUser $newUser = null)
    {
        $this->roles = new ArrayCollection();

        if (null !== $newUser) {
            $this->lidnr = $newUser->getLidnr();
            $this->member = $newUser->getMember();
        }
    }

    /**
     * Return the `lidnr` of this user, generalised to `id` for the {@link AuthenticationService}.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->getLidnr();
    }

    /**
     * Get the membership number.
     *
     * @return int
     */
    public function getLidnr(): int
    {
        return $this->lidnr;
    }

    /**
     * Get the user's email address.
     *
     * @return string|null
     */
    public function getEmail(): string|null
    {
        return $this->member->getEmail();
    }

    /**
     * Get the password hash.
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set the password hash.
     *
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * Get the user's roles.
     *
     * @return Collection
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * Get the member information of this user.
     *
     * @return MemberModel
     */
    public function getMember(): MemberModel
    {
        return $this->member;
    }

    /**
     * Get the user's role names.
     *
     * @return array Role names
     */
    public function getRoleNames(): array
    {
        $names = [];

        foreach ($this->getRoles() as $role) {
            $names[] = $role->getRole();
        }

        return $names;
    }

    /**
     * @return string
     */
    public function getRoleId(): string
    {
        $roleNames = $this->getRoleNames();
        if (in_array('admin', $roleNames) || $this->getMember()->isBoardMember()) {
            return 'admin';
        }

        if (in_array('company_admin', $roleNames)) {
            return 'company_admin';
        }

        if (count($this->getMember()->getCurrentOrganInstallations()) > 0) {
            return 'active_member';
        }

        if (empty($roleNames)) {
            if (MembershipTypes::Graduate === $this->getMember()->getType()) {
                return 'graduate';
            }

            return 'user';
        }

        if (in_array('photo_guest', $roleNames)) {
            return 'photo_guest';
        }

        throw new RuntimeException(
            sprintf('Could not determine user role unambiguously for user %s', $this->getLidnr())
        );
    }

    /**
     * @param ArrayCollection $roles
     */
    public function setRoles(ArrayCollection $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @param int $lidnr
     */
    public function setLidnr(int $lidnr): void
    {
        $this->lidnr = $lidnr;
    }

    /**
     * @param MemberModel $member
     */
    public function setMember(MemberModel $member): void
    {
        $this->member = $member;
    }

    /**
     * @return DateTime|null
     */
    public function getPasswordChangedOn(): ?DateTime
    {
        return $this->passwordChangedOn;
    }

    /**
     * @param DateTime $passwordChangedOn
     */
    public function setPasswordChangedOn(DateTime $passwordChangedOn): void
    {
        $this->passwordChangedOn = $passwordChangedOn;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'lidnr' => $this->getLidnr(),
            'member' => $this->getMember()->toArray(),
        ];
    }

    /**
     * Get the user's resource ID.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'user';
    }
}
