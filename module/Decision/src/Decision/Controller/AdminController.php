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
        $type = $this->params()->fromRoute('type');
        $number = $this->params()->fromRoute('number');
        $meetings = $service->getMeetingsByType('AV');
        $meetings = array_merge($meetings, $service->getMeetingsByType('VV'));
        if (is_null($number) && count($meetings) > 0) {
            $number = $meetings[0]->getNumber();
            $type = $meetings[0]->getType();
        }
        $request = $this->getRequest();
        $success = false;
        if ($request->isPost()) {
            if ($service->uploadDocument($request->getPost(), $request->getFiles())) {
                $success = true;
            }
        }
        $meeting = $this->getDecisionService()->getMeeting($type, $number);
        return new ViewModel([
            'form' => $service->getDocumentForm(),
            'meetings' => $meetings,
            'meeting' => $meeting,
            'number' => $number,
            'success' => $success
        ]);
    }

    public function deleteDocumentAction()
    {
        $this->getDecisionService()->deleteDocument($this->getRequest()->getPost());
        return $this->redirect()->toRoute('admin_decision/document');
    }

    public function changePositionDocumentAction()
    {
        if (!$this->getRequest()->isPost()) {
            // TODO: Throw 405 Method Not Allowed error
        }

        $documentId = $this->params()->fromPost('document');
        $direction = $this->params()->fromPost('direction');

        switch ($direction) {
            case 'up':
                $moveDown = false;
                break;
            case 'down':
                $moveDown = true;
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf("Invalid value for direction parameter: '%s'", $direction)
                );
        }

        // Update ordering document
        $this->getDecisionService()->changePositionDocument($documentId, $moveDown);

        // Redirect to correct meeting page
        $meeting = $this->getDecisionService()
            ->getMeetingDocument($documentId)
            ->getMeeting();

        return $this->redirect()->toRoute('admin_decision/document', [
            'type'   => $meeting->getType(),
            'number' => $meeting->getNumber()
        ]);
    }

    public function authorizationsAction()
    {
        $meetings = $this->getDecisionService()->getMeetingsByType('AV');
        $number = $this->params()->fromRoute('number');
        $authorizations = [];
        if (is_null($number) && count($meetings) > 0) {
            $number = $meetings[0]->getNumber();
        }

        if (!is_null($number)) {
            $authorizations = $this->getDecisionService()->getAllAuthorizations($number);
        }

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
