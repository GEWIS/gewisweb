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
     * Search decisions.
     */
    public function searchAction()
    {
        return new ViewModel(array(
            'form' => $this->getServiceLocator()->get('decision_form_searchdecision')
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
