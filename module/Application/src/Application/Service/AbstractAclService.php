<?php

namespace Application\Service;

use User\Model\User;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Resource\ResourceInterface;

abstract class AbstractAclService extends AbstractService
{

    /**
     * Get the ACL.
     *
     * @return Acl
     */
    abstract public function getAcl();

    /**
     * Get the default resource ID.
     *
     * This is used by {@link isAllowed()} when no resource is specified.
     *
     * @return string
     */
    abstract protected function getDefaultResourceId();

    /**
     * Get the current user's role.
     *
     * @return User|string
     */
    public function getRole()
    {
        return $this->getServiceManager()->get('user_role');
    }

    /**
     * Check if a operation is allowed for the current role.
     *
     * If no resource is given, this will use the resource given by
     * {@link getDefaultResourceId()}.
     *
     * @param string $operation Operation to be checked.
     * @param string|ResourceInterface $resource Resource to be checked
     *
     * @return boolean
     */
    public function isAllowed($operation, $resource = null)
    {
        if (null === $resource) {
            $resource = $this->getDefaultResourceId();
        }

        return $this->getAcl()->isAllowed(
            $this->getRole(),
            $resource,
            $operation
        );
    }
}
