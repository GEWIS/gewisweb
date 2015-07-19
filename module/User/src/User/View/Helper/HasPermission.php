<?php
namespace User\View\Helper;

use Zend\Di\ServiceLocator;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Helper\AbstractHelper;
use Zend\Permissions\Acl\Acl;

class HasPermission extends AbstractHelper implements ServiceLocatorAwareInterface
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $sl;

    /**
     * @var string
     */
    protected $role;

    /**
     * Get the exam URL.
     *
     * @param string $module
     * @return bool $module
     */
    public function __invoke($module, $resource, $action)
    {
        $acl = $this->sl->getServiceLocator()->get($module . '_acl');
        return $acl->isAllowed(
            $this->getrole(),
            $resource,
            $action
        );
    }

    /**
     * Set the acl service
     *
     * @param Acl $acl
     */
    public function setAclService(Acl $acl)
    {
        $this->acl = $acl;
    }

    /**
     * Get the acl service
     *
     * @return Acl
     */
    public function getAclService()
    {
        return $this->acl;
    }


    /**
     * @return string
     */
    public function getRole()
    {
        return $this->getServiceLocator()->getServiceLocator()->get('user_role');
    }

    /**
     * Set service manager
     *
     * @param ServiceManager $serviceManager
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceManager)
    {
        $this->sl = $serviceManager;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->sl;
    }


}
