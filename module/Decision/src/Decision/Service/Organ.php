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
