<?php

namespace Education\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class EducationController extends AbstractActionController {

    public function indexAction()
    {
        return new ViewModel(array(
            'form' => $this->getExamService()->getSearchCourseForm()
        ));
    }

    public function courseAction()
    {
        $code = $this->params()->fromRoute('code');
        return new ViewModel(array(
            'course' => $this->getExamService()->getCourse($code)
        ));
    }

    /**
     * Get the exam service.
     */
    public function getExamService()
    {
        return $this->getServiceLocator()->get('education_service_exam');
    }
}
