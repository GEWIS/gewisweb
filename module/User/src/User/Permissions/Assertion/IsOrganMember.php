<?php

namespace User\Permissions\Assertion;

use DateTime;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\RoleInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Assertion\AssertionInterface;

use User\Model\User;
use User\Permissions\Resource\OrganResourceInterface;

use Decision\Model\OrganMember;
use Decision\Model\Organ;

/**
 * Assertion to check if the user is a member of the organ tied to the resource.
 */
class IsOrganMember implements AssertionInterface
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
        if (!$role instanceof User || !$resource instanceof OrganResourceInterface) {
            return false;
        }

        $member = $role->getMember();
        $organ = $resource->getResourceOrgan();

        if (!$organ instanceof Organ) {
            return false;
        }

        foreach ($member->getOrganInstallations() as $organInstall) {
            if ($organInstall->getOrgan()->getId() === $organ->getId()
                && $this->isCurrentMember($organInstall)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this is a current organ member.
     *
     * @param OrganMember $organMember
     *
     * @return bool
     */
    protected function isCurrentMember(OrganMember $organMember)
    {
        $now = new DateTime();
        return $organMember->getInstallDate() <= $now &&
            (null === $organMember->getDischargeDate() || $organMember->getDischargeDate() >= $now);
    }
}
