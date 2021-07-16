<?php

namespace Application\Service;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

abstract class AbstractAclService
{
    /**
     * Get the ACL.
     *
     * @return Acl
     */
    abstract protected function getAcl(): Acl;

    /**
     * Get the current user's role.
     *
     * @return RoleInterface|string
     */
    abstract protected function getRole();

    /**
     * Check if a operation is allowed for the current role.
     *
     * @param string $operation operation to be checked
     * @param string|ResourceInterface $resource Resource to be checked
     *
     * @return bool
     */
    public function isAllowed(string $operation, $resource): bool
    {
        return $this->getAcl()->isAllowed(
            $this->getRole(),
            $resource,
            $operation
        );
    }
}
