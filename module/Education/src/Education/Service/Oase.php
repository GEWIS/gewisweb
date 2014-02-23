<?php

namespace Education\Service;

use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\Soap\Client as SoapClient;

/**
 * Exam service.
 */
class Oase implements ServiceManagerAwareInterface
{

    /**
     * Service manager.
     *
     * @var ServiceManager
     */
    protected $sm;

    /**
     * Update course info from OASE.
     *
     * This method will get course info from OASE and update our database.
     */
    public function update()
    {
        $studies = $this->getOaseStudyService()->getStudies();

        $this->getStudyMapper()->persistMultiple($studies);

        echo "Updated all studies\n";
    }

    /**
     * Get the study mapper.
     *
     * @return \Education\Mapper\Study
     */
    public function getStudyMapper()
    {
        return $this->sm->get('education_mapper_study');
    }

    /**
     * Get the OASE service.
     *
     * @return \Education\Oase\Service
     */
    public function getOaseStudyService()
    {
        return $this->sm->get('education_oase_service_study');
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

