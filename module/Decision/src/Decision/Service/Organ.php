<?php

namespace Decision\Service;

use Decision\Model\Organ as OrganModel;
use Decision\Mapper\Organ as OrganMapper;

use Zend\ServiceManager\ServiceManager,
    Zend\ServiceManager\ServiceManagerAwareInterface;

/**
 * User service.
 */
class Organ implements ServiceManagerAwareInterface
{

    /**
     * Service manager.
     *
     * @var ServiceManager
     */
    protected $sm;

    /**
     * Get organs.
     *
     * @return array Of organs.
     */
    public function getOrgans()
    {
        return $this->getOrganMapper()->findAll();
    }

    /**
     * Get the organ mapper.
     *
     * @return OrganMapper.
     */
    public function getOrganMapper()
    {
        return $this->sm->get('decision_mapper_organ');
    }

    /**
     * Check if a operation is allowed for the current user.
     *
     * @param string $operation Operation to be checked.
     * @param string|ResourceInterface $resource Resource to be checked
     *
     * @return boolean
     */
    public function isAllowed($operation, $resource = 'organ')
    {
        return $this->getAcl()->isAllowed(
            $this->getRole(),
            $resource,
            $operation
        );
    }

    /**
     * Get the current user's role.
     *
     * @return UserModel|string
     */
    public function getRole()
    {
        return $this->sm->get('user_role');
    }

    /**
     * Get the Acl.
     *
     * @return Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->sm->get('decision_acl');
    }

    /**
     * Set the service manager.
     *
     * @param ServiceManager $sm
     */
    public function setServiceManager(ServiceManager $sm)
    {
        $this->sm = $sm;
    }

    /**
     * Get the service manager.
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->sm;
    }
}
