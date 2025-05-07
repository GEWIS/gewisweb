<?php

declare(strict_types=1);

namespace User\Permissions\Assertion;

use Frontpage\Model\Page as PageModel;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Override;

/**
 * Assertion to check if whoever is trying to view the page is allowed to view the page.
 */
class IsAllowedToViewPage implements AssertionInterface
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
        if (!$resource instanceof PageModel) {
            return false;
        }

        $requiredRole = $resource->getRequiredRole()->value;

        return $role->getRoleId() === $requiredRole || $acl->inheritsRole($role, $requiredRole);
    }
}
