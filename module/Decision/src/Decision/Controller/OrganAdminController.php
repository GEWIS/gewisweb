<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class OrganAdminController extends AbstractActionController
{

    /**
     * @var \Decision\Service\Organ
     */
    private $organService;

    public function __construct(\Decision\Service\Organ $organService)
    {
        $this->organService = $organService;
    }

    /**
     * Index action, shows all active organs.
     */
    public function indexAction()
    {
        return new ViewModel([
            'organs' => $this->organService->getEditableOrgans()
        ]);
    }

    /**
     * Show an organ.
     */
    public function editAction()
    {
        $organId = $this->params()->fromRoute('organ_id');
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($this->organService->updateOrganInformation($organId, $request->getPost(), $request->getFiles())) {
                $this->redirect()->toUrl($this->url()->fromRoute('admin_organ'));
            }
        }

        $organInformation = $this->organService->getEditableOrganInformation($organId);
        $form = $this->organService->getOrganInformationForm($organInformation);

        return new ViewModel([
            'form' => $form
        ]);
    }
}
