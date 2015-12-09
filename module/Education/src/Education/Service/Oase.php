<?php

namespace Education\Service;

use Application\Service\AbstractService;

use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\Soap\Client as SoapClient;

/**
 * Exam service.
 */
class Oase extends AbstractService
{

    /**
     * Update course info from OASE.
     *
     * This method will get course info from OASE and update our database.
     */
    public function update()
    {
        // determine the year to obtain the study in
        $date = new \DateTime();
        $date = $date->sub(new \DateInterval('P8M'));
        $year = $date->format('Y');

        $studies = $this->getOaseStudyService()->getStudies($year);

        $this->getStudyMapper()->persistMultiple($studies);

        echo "Updated all studies\n";

        $courses = $this->getOaseCourseService()->getCourses($studies, $year);

        $this->getCourseMapper()->persistMultiple($courses);

        echo "Updated all courses\n";

        // flush all updates
        $this->getStudyMapper()->flush();
        echo "Flushed\n";
    }

    /**
     * Get course info.
     *
     * @param string $code
     *
     * @return SimpleXMLElement
     */
    public function getCourse($code)
    {
        return $this->getOaseCourseService()->getCourse($code);
    }

    /**
     * Get all the studies.
     *
     * @return array Of all studies
     */
    public function getAllStudies()
    {
        return $this->getOaseStudyService()->getAllStudies();
    }

    /**
     * Get the course mapper.
     *
     * @return \Education\Mapper\Course
     */
    public function getCourseMapper()
    {
        return $this->sm->get('education_mapper_course');
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
}
