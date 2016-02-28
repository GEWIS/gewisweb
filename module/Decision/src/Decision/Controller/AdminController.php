<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AdminController extends AbstractActionController
{

    /**
     * Notes upload action.
     */
    public function notesAction()
    {
        $service = $this->getDecisionService();
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($service->uploadNotes($request->getPost(), $request->getFiles())) {
                return new ViewModel([
                    'success' => true
                ]);
            }
        }

        return new ViewModel([
            'form' => $service->getNotesForm()
        ]);
    }

    /**
     * Document upload action.
     */
    public function documentAction()
    {
        $service = $this->getDecisionService();
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($service->uploadDocument($request->getPost(), $request->getFiles())) {
                return new ViewModel([
                    'success' => true
                ]);
            }
        }

        return new ViewModel([
            'form' => $service->getDocumentForm()
        ]);
    }

    public function authorizationsAction()
    {
        $number = $this->params()->fromRoute('number');
        $authorizations = [];
        if (!is_null($number)) {
            $authorizations = $this->getDecisionService()->getAllAuthorizations($number);
        }
        $meetings = $this->getDecisionService()->getMeetingsByType('AV');

        return new ViewModel([
            'meetings' => $meetings,
            'authorizations' => $authorizations,
            'number' => $number
        ]);
    }

    /**
     * Get the decision service.
     */
    public function getDecisionService()
    {
        return $this->getServiceLocator()->get('decision_service_decision');
    }

}
