<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class OrganAdminController extends AbstractActionController
{

    /**
     * Index action, shows all active organs.
     */
    public function indexAction()
    {
        return new ViewModel([
            'organs' => $this->getOrganService()->getEditableOrgans()
        ]);
    }

    /**
     * Show an organ.
     */
    public function editAction()
    {
        $organService = $this->getOrganService();
        $organId = $this->params()->fromRoute('organ_id');
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($organService->updateOrganInformation($organId, $request->getPost(), $request->getFiles())) {
                $this->redirect()->toUrl($this->url()->fromRoute('admin_organ'));
            }
        }

        $organInformation = $organService->getEditableOrganInformation($organId);
        $form = $organService->getOrganInformationForm($organInformation);

        return new ViewModel([
            'form' => $form
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
