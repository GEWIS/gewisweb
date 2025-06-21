<?php

declare(strict_types=1);

namespace User\Permissions\Assertion;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Override;

/**
 * Assertion to check if the user has created the resource or if the user is a
 * member of the organ tied to the resource.
 */
class IsCreatorOrOrganMember implements AssertionInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function assert(
        Acl $acl,
        ?RoleInterface $role = null,
        ?ResourceInterface $resource = null,
        $privilege = null,
    ): bool {
        $isCreator = new IsCreator();
        $isOrganMember = new IsOrganMember();

        return $isCreator->assert($acl, $role, $resource, $privilege)
            || $isOrganMember->assert($acl, $role, $resource, $privilege);
    }
}
