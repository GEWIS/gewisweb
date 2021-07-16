<?php

namespace User\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * User role model.
 *
 * This specifies all the roles of a user.
 *
 * @ORM\Entity
 */
class UserRole
{
    /**
     * Id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * The membership number of the user with this role.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User", inversedBy="roles")
     * @ORM\JoinColumn(referencedColumnName="lidnr")
     */
    protected $lidnr;

    /**
     * The user's role.
     *
     * @ORM\Column(type="string")
     */
    protected $role;

    /**
     * Get the id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the membership number.
     *
     * @return int
     */
    public function getLidnr()
    {
        return $this->lidnr;
    }

    /**
     * Set the membership number.
     *
     * @param int $lidnr
     */
    public function setLidnr($lidnr)
    {
        $this->lidnr = $lidnr;
    }

    /**
     * Get the role.
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set the role.
     *
     * @param string $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }
}
