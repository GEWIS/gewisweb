<?php

namespace Decision\Controller;

use Decision\Service\Organ;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class OrganAdminController extends AbstractActionController
{
    /**
     * @var Organ
     */
    private $organService;

    public function __construct(Organ $organService)
    {
        $this->organService = $organService;
    }

    /**
     * Index action, shows all active organs.
     */
    public function indexAction()
    {
        return new ViewModel(
            [
                'organs' => $this->organService->getEditableOrgans(),
            ]
        );
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

        return new ViewModel(
            [
                'form' => $form,
            ]
        );
    }
}
