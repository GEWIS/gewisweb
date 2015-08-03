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
        return new ViewModel(array(
            'meetings' => $this->getDecisionService()->getMeetings()
        ));
    }

    /**
     * View a meeting.
     */
    public function viewAction()
    {
        $type = $this->params()->fromRoute('type');
        $number = $this->params()->fromRoute('number');

        try {
            $meeting = $this->getDecisionService()->getMeeting($type, $number);
            return new ViewModel(array(
                'meeting' => $meeting,
                'notes'   => $this->getDecisionService()->getMeetingNotes($meeting)
            ));
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
                return new ViewModel(array(
                    'result' => $result,
                    'form' => $service->getSearchDecisionForm()
                ));
            }
        }

        return new ViewModel(array(
            'form' => $service->getSearchDecisionForm()
        ));
    }

    /**
     * Get the decision service.
     */
    public function getDecisionService()
    {
        return $this->getServiceLocator()->get('decision_service_decision');
    }

}
