<?php

namespace User\Permissions\Assertion;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

/**
 * Assertion to check if the user has created the resource or if the user is a
 * member of the organ tied to the resource.
 */
class IsCreatorOrOrganMember implements AssertionInterface
{
    /**
     * Returns true if and only if the assertion conditions are met.
     *
     * This method is passed the ACL, Role, Resource, and privilege to which the authorization query applies. If the
     * $role, $resource, or $privilege parameters are null, it means that the query applies to all Roles, Resources, or
     * privileges, respectively.
     *
     * @param RoleInterface $role
     * @param ResourceInterface $resource
     * @param string $privilege
     *
     * @return bool
     */
    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        $isCreator = new IsCreator();
        $isOrganMember = new IsOrganMember();

        return $isCreator->assert($acl, $role, $resource, $privilege) || $isOrganMember->assert($acl, $role, $resource, $privilege);
    }
}
