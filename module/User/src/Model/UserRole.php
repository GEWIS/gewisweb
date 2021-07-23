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
    protected int $id;

    /**
     * The membership number of the user with this role.
     */
    #[ManyToOne(
        targetEntity: "User\Model\User",
        inversedBy: "roles",
    )]
    #[JoinColumn(
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected int $lidnr;

    /**
     * The user's role.
     */
    #[Column(type: "string")]
    protected string $role;

    /**
     * Get the id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
     * Set the membership number.
     *
     * @param int $lidnr
     */
    public function setLidnr(int $lidnr): void
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
