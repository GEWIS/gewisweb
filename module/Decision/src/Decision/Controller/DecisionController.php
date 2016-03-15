<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class DecisionController extends AbstractActionController
{

    /**
     * Index action, shows meetings.
     */
    public function indexAction()
    {
        return new ViewModel([
            'meetings' => $this->getDecisionService()->getMeetings()
        ]);
    }

    /**
     * Download meeting notes
     */
    public function notesAction()
    {
        $type = $this->params()->fromRoute('type');
        $number = $this->params()->fromRoute('number');

        try {
            $meeting = $this->getDecisionService()->getMeeting($type, $number);
            $response = $this->getDecisionService()->getMeetingNotesDownload($meeting);
            if (is_null($response)) {
                return $this->notFoundAction();
            }

            return $response;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return $this->notFoundAction();
        }

    }

    public function documentAction()
    {
        $id = $this->params()->fromRoute('id');

        try {
            $meetingDocument = $this->getDecisionService()->getMeetingDocument($id);
            $response = $this->getDecisionService()->getMeetingDocumentDownload($meetingDocument);
            if (is_null($response)) {
                return $this->notFoundAction();
            }

            return $response;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return $this->notFoundAction();
        }
    }

    /**
     * View a meeting.
     */
    public function viewAction()
    {
        $type = $this->params()->fromRoute('type');
        $number = $this->params()->fromRoute('number');
        $service = $this->getDecisionService();

        try {
            $meeting = $service->getMeeting($type, $number);

            return new ViewModel([
                'meeting' => $meeting
            ]);
        } catch (\Doctrine\ORM\NoResultException $e) {
            return $this->notFoundAction();
        }
    }

    /**
     * Search decisions.
     */
    public function searchAction()
    {
        $service = $this->getDecisionService();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $result = $service->search($request->getPost());

            if (null !== $result) {
                return new ViewModel([
                    'result' => $result,
                    'form' => $service->getSearchDecisionForm()
                ]);
            }
        }

        return new ViewModel([
            'form' => $service->getSearchDecisionForm()
        ]);
    }

    public function authorizationsAction()
    {
        $meeting = $this->getDecisionService()->getLatestAV();
        $authorization = null;
        if (!is_null($meeting)) {
            $authorization = $this->getDecisionService()->getUserAuthorization($meeting->getNumber());
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $authorization = $this->getDecisionService()->createAuthorization($request->getPost());
            if ($authorization) {
                return new ViewModel([
                    'meeting' => $meeting,
                    'authorization' => $authorization
                ]);
            }
        }

        $form = $this->getDecisionService()->getAuthorizationForm();

        return new ViewModel([
            'meeting' => $meeting,
            'authorization' => $authorization,
            'form' => $form
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
