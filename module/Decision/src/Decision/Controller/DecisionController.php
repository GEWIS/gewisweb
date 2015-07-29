<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class DecisionController extends AbstractActionController
{

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
