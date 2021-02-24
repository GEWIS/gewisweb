<?php

namespace User\Permissions\Assertion;

use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\RoleInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Assertion\AssertionInterface;

use User\Model\User;

use User\Permissions\Resource\CreatorResourceInterface;

/**
 * Assertion to check if the user has created some entity
 */
class IsCreator implements AssertionInterface
{


    /**
     * Returns true if and only if the assertion conditions are met
     *
     * This method is passed the ACL, Role, Resource, and privilege to which the authorization query applies. If the
     * $role, $resource, or $privilege parameters are null, it means that the query applies to all Roles, Resources, or
     * privileges, respectively.
     *
     * @param Acl $acl
     * @param RoleInterface $role
     * @param ResourceInterface $resource
     * @param string $privilege
     * @return bool
     */
    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (!$role instanceof User) {
            return false;
        }
        if (!$resource instanceof CreatorResourceInterface) {
            return false;
        }
        $creator = $resource->getResourceCreator();

        if (!$creator instanceof User) {
            return false;
        }

        return $role->getLidnr() === $creator->getLidnr();
    }
}
