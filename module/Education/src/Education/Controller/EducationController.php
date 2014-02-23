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

    /**
     * Get the exam service.
     */
    public function getExamService()
    {
        return $this->getServiceLocator()->get('education_service_exam');
    }
}
