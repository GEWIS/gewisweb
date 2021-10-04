<?php

namespace User\Model;


use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
};

/**
 * User role model.
 *
 * This specifies all the roles of a user.
 */
#[Entity]
class UserRole
{
    /**
     * Id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * The membership number of the user with this role.
     */
    #[ManyToOne(
        targetEntity: User::class,
        inversedBy: "roles",
    )]
    #[JoinColumn(
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected User $lidnr;

    /**
     * The user's role.
     */
    #[Column(type: "string")]
    protected string $role;

    /**
     * Get the id.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the membership number.
     *
     * @return User
     */
    public function getLidnr(): User
    {
        return $this->lidnr;
    }

    /**
     * Set the membership number.
     *
     * @param User $lidnr
     */
    public function setLidnr(User $lidnr): void
    {
        $this->lidnr = $lidnr;
    }

    /**
     * Get the role.
     *
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Set the role.
     *
     * @param string $role
     */
    public function setRole(string $role): void
    {
        $this->role = $role;
    }
}
