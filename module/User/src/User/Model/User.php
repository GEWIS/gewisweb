<?php

namespace User\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Zend\Permissions\Acl\Role\RoleInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * User model.
 *
 * @ORM\Entity
 */
class User implements RoleInterface, ResourceInterface
{

    /**
     * The membership number.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $lidnr;

    /**
     * The user's email address.
     * Deprecated
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * The user's password.
     *
     * @ORM\Column(type="string")
     */
    protected $password;

    /**
     * User roles
     *
     * @ORM\OneToMany(targetEntity="User\Model\UserRole", mappedBy="lidnr")
     */
    protected $roles;

    /**
     * User sessions
     *
     * @ORM\OneToMany(targetEntity="User\Model\Session", mappedBy="user")
     */
    protected $sessions;

    /**
     * The corresponding member for this user.
     *
     * @ORM\OneToOne(targetEntity="Decision\Model\Member", fetch="EAGER")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $member;

    /**
     * Constructor
     */
    public function __construct(NewUser $newUser = null)
    {
        $this->roles = new ArrayCollection();

        if (null !== $newUser) {
            $this->lidnr = $newUser->getLidnr();
            $this->email = $newUser->getEmail();
            $this->member = $newUser->getMember();
        }
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
     * Get the user's email address.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->member->getEmail();
    }

    /**
     * Get the password hash.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the password hash.
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Get the user's roles.
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Get the member information of this user.
     *
     * @return \Decision\Model\Member
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * Get the user's role names.
     *
     * @return array Role names
     */
    public function getRoleNames()
    {
        $roles = [];

        foreach ($this->getRoles() as $role) {
            $roles[] = $role->getRole();
        }

        return $roles;
    }

    /**
     * Get the user's role ID.
     *
     * @return string
     */
    public function getRoleId()
    {
        return 'user_' . $this->getLidnr();
    }

    /**
     * @param array $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    public function toArray()
    {
        return [
            'lidnr' => $this->getLidnr(),
            'email' => $this->getEmail(),
            'member' => $this->getMember()->toArray()
        ];
    }
    /**
     * Get the user's resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'user';
    }
}
