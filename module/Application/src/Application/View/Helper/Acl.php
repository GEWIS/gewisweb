<?php

namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;


class Acl extends AbstractHelper
{

    /**
     * Service locator
     *
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $locator;

    /**
     * Acl
     */
    protected $acl;

    public function isAllowed($resource, $operation)
    {
        return $this->acl->isAllowed(
            $this->getRole(),
            $resource,
            $operation
        );
    }

    /**
     * Get the current user's role.
     *
     * @return User|string
     */
    public function getRole()
    {
        return $this->getServiceLocator()->get('user_role');
    }

    /**
     * Returns the Acl for a specific module
     *
     * @param string $factory Acl factory to load
     *
     * @return \Application\View\Helper\Acl
     */
    public function __invoke($factory)
    {
        $this->acl = $this->getServiceLocator()->get($factory);
        if ($this->acl instanceof \Zend\Permissions\Acl\Acl) {
            return $this;
        } else {
            throw new \Zend\Code\Exception\InvalidArgumentException(
                'Provided factory does not exist or does not return an Acl instance'
            );
        }
    }

    /**
     * Get the service locator.
     *
     * @return \Zend\ServiceManager\ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->locator;
    }

    /**
     * Set the service locator
     *
     * @param \Zend\ServiceManager\ServiceLocatorInterface
     */
    public function setServiceLocator($locator)
    {
        $this->locator = $locator;
    }
}
