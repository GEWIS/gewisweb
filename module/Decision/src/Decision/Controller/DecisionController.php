<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class DecisionController extends AbstractActionController
{

    /**
     * Index action, shows all organs.
     */
    public function indexAction()
    {
        return new ViewModel(array(
            'organs' => $this->getOrganService()->getOrgans()
        ));
    }

    /**
     * Get the organ service.
     */
    public function getOrganService()
    {
        return $this->getServiceLocator()->get('decision_service_organ');
    }
}
