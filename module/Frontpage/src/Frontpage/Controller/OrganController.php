<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class OrganController extends AbstractActionController
{
    public function listAction()
    {
        $type = $this->params()->fromRoute('type');
        $type = $type === 'committees' ? 'committee' : 'fraternity';
        $organs = $this->getOrganService()->findOrgansByType($type);
        $vm = new ViewModel([
            'organs' => $organs
        ]);
        $vm->setTemplate('organ/' . $type . '-list');
        return $vm;
    }

    public function organAction()
    {
        $abbr = $this->params()->fromRoute('abbr');
        $organ = $this->getOrganService()->findOrganByAbbr($abbr);
        return new ViewModel([
            'organ' => $organ
        ]);
    }

    /**
     * Get the organ service.
     */
    public function getOrganService()
    {
        return $this->getServiceLocator()->get('decision_service_organ');
    }
}