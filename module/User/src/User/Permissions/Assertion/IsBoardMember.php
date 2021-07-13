<?php

namespace User\Permissions\Assertion;

use DateTime;
use Decision\Model\BoardMember;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use User\Model\User;

/**
 * Assertion to check if the user is a board member.
 */
class IsBoardMember implements AssertionInterface
{
    /**
     * Returns true if and only if the assertion conditions are met.
     *
     * This method is passed the ACL, Role, Resource, and privilege to which the authorization query applies. If the
     * $role, $resource, or $privilege parameters are null, it means that the query applies to all Roles, Resources, or
     * privileges, respectively.
     *
     * @param RoleInterface     $role
     * @param ResourceInterface $resource
     * @param string            $privilege
     *
     * @return bool
     */
    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (!$role instanceof User) {
            return false;
        }
        $member = $role->getMember();

        foreach ($member->getBoardInstallations() as $boardInstall) {
            if ($this->isCurrentBoard($boardInstall)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this is a current board member.
     *
     * @return bool
     */
    protected function isCurrentBoard(BoardMember $boardMember)
    {
        $now = new DateTime();

        return $boardMember->getInstallDate() <= $now &&
            (null === $boardMember->getDischargeDate() || $boardMember->getDischargeDate() >= $now);
    }
}
