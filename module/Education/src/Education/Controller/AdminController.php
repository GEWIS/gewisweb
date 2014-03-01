<?php

namespace Education\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AdminController extends AbstractActionController {

    public function indexAction()
    {
    }

    public function uploadAction()
    {
        if (!$this->getExamService()->isAllowed('upload')) {
            $translator = $this->getServiceLocator()->get('translator');
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to upload exams')
            );
        }

        return new ViewModel(array(
            'form' => $this->getExamService()->getUploadForm()
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
