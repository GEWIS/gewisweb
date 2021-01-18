<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class OrganController extends AbstractActionController
{

    /**
     * Index action, shows all active organs.
     */
    public function indexAction()
    {
        return new ViewModel([
            'organs' => $this->getOrganService()->getOrgans()
        ]);
    }

    /**
     * Show an organ.
     */
    public function showAction()
    {
        $organId = $this->params()->fromRoute('organ');
        $organService = $this->getOrganService();
        try {
            $organ = $organService->getOrgan($organId);
            $organMemberInformation = $organService->getOrganMemberInformation($organ);
            return new ViewModel(array_merge([
                'organ' => $organ
            ], $organMemberInformation));
        } catch (\Doctrine\ORM\NoResultException $e) {
            return $this->notFoundAction();
        }
    }

    /**
     * Get the organ service.
     */
    public function getOrganService()
    {
        return $this->getServiceLocator()->get('decision_service_organ');
    }
}
