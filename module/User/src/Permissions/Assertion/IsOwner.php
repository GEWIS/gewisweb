<?php

declare(strict_types=1);

namespace User\Permissions\Assertion;

use Decision\Model\Member;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use User\Model\User;
use User\Permissions\Resource\OwnerResourceInterface;

/**
 * Assertion to check if the user has created some entity.
 */
class IsOwner implements AssertionInterface
{
    /**
     * @inheritDoc
     */
    public function assert(
        Acl $acl,
        ?RoleInterface $role = null,
        ?ResourceInterface $resource = null,
        $privilege = null,
    ): bool {
        if (
            !$role instanceof User
            || !$resource instanceof OwnerResourceInterface
        ) {
            return false;
        }

        return $role->getLidnr() === $resource->getResourceOwner()->getLidnr();
    }
}
