<?php

namespace User\Permissions\Assertion;

use Decision\Model\Enums\MembershipTypes;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Photo\Model\{Album, Photo};
use User\Model\User;

/**
 * Assertion to check if when the user is a graduate, that the album they are trying to view is before their membership
 * ended.
 */
class IsAfterMembershipEnded implements AssertionInterface
{
    /**
     * Returns true if and only if the assertion conditions are met.
     *
     * This method is passed the ACL, Role, Resource, and privilege to which the authorization query applies. If the
     * $role, $resource, or $privilege parameters are null, it means that the query applies to all Roles, Resources, or
     * privileges, respectively.
     *
     * @param Acl $acl
     * @param RoleInterface|null $role
     * @param ResourceInterface|null $resource
     * @param string|null $privilege
     *
     * @return bool
     */
    public function assert(
        Acl $acl,
        ?RoleInterface $role = null,
        ?ResourceInterface $resource = null,
        $privilege = null,
    ): bool {
        if (
            !$role instanceof User
            || (!$resource instanceof Album && !$resource instanceof Photo)
        ) {
            return false;
        }

        // If the member is not a graduate this check should never have been called in the first place, but just make
        // sure that we are only checking graduates.
        if (MembershipTypes::Graduate !== $role->getMember()->getType()) {
            return false;
        }

        if ($resource instanceof Photo) {
            $resource = $resource->getAlbum();
        }

        return $role->getMember()->getMembershipEndsOn() < $resource->getStartDateTime();
    }
}
