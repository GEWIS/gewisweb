<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class OrganController extends AbstractActionController
{
    public function committeeListAction()
    {
        $committees = $this->getOrganService()->findActiveOrgansByType('committee');
        $vm = new ViewModel([
            'committees' => $committees
        ]);
        return $vm;
    }

    public function fraternityListAction()
    {
        $activeFraternities = $this->getOrganService()->findActiveOrgansByType('fraternity');
        $abrogatedFraternities = $this->getOrganService()->findAbrogatedOrgansByType('fraternity');
        $vm = new ViewModel([
            'activeFraternities' => $activeFraternities,
            'abrogatedFraternities' => $abrogatedFraternities
        ]);
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