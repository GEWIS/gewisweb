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

        $data = $this->getOaseCourseService()->getCourses($studies);

        foreach ($data as $key => $el) {
            echo $key . "\n";
            foreach ($el['studies'] as $study) {
                echo $study->getName() . "\n";
            }
            echo "\n";
        }

        // flush all updates
        $this->getStudyMapper()->flush();
        echo "Flushed\n";
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
     * Get the OASE course service.
     *
     * @return \Education\Oase\Service\Course
     */
    public function getOaseCourseService()
    {
        return $this->sm->get('education_oase_service_course');
    }

    /**
     * Get the OASE study service.
     *
     * @return \Education\Oase\Service\Study
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

