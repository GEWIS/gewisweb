<?php

namespace User\Model;

use Decision\Model\Member;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use RuntimeException;

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
     * Deprecated.
     *
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
     * User roles.
     *
     * @ORM\OneToMany(targetEntity="User\Model\UserRole", mappedBy="lidnr")
     */
    protected $roles;

    /**
     * User sessions.
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
     * Constructor.
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
     * @return Collection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Get the member information of this user.
     *
     * @return Member
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
        if (in_array('admin', $roleNames)) {
            return 'admin';
        }

        if (in_array('company_admin', $roleNames)) {
            return 'company_admin';
        }

        if (empty($roleNames)) {
            return 'user';
        }

        if (count($this->getMember()->getCurrentOrganInstallations()) > 0) {
            return 'active_member';
        }

        if (in_array('photo_guest', $roleNames)) {
            return 'photo_guest';
        }

        throw new RuntimeException(
            sprintf('Could not determine user role unambiguously for user %s', $this->getLidnr())
        );
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
            'member' => $this->getMember()->toArray(),
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
