<?php

namespace Application\Service;

use Application\Model\IdentityInterface;
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
    abstract protected function getRole(): string|RoleInterface;

    /**
     * Check if a operation is allowed for the current role.
     *
     * @param string $operation operation to be checked
     * @param string|ResourceInterface $resource Resource to be checked
     *
     * @return bool
     */
    public function isAllowed(
        string $operation,
        ResourceInterface|string $resource,
    ): bool {
        return $this->getAcl()->isAllowed(
            $this->getRole(),
            $resource,
            $operation
        );
    }

    /**
     * Gets the user identity if logged in or null otherwise
     *
     * @return IdentityInterface|null the current logged in user
     */
    abstract public function getIdentity(): ?IdentityInterface;

    /**
     * Checks whether the user is logged in
     *
     * @return bool true if the user is logged in, false otherwise
     */
    public function hasIdentity(): bool
    {
        $identity = $this->getIdentity();

        if ($identity === null) {
            return false;
        }

        return true;
    }
}
