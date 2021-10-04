<?php

namespace Decision\Controller;

use Decision\Service\Organ as OrganService;
use Laminas\Form\FormInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class OrganAdminController extends AbstractActionController
{
    /**
     * @var OrganService
     */
    private OrganService $organService;

    /**
     * OrganAdminController constructor.
     *
     * @param OrganService $organService
     */
    public function __construct(OrganService $organService)
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
        $organInformation = $this->organService->getEditableOrganInformation($organId);

        if (false === $organInformation) {
            return $this->notFoundAction();
        }

        $form = $this->organService->getOrganInformationForm($organInformation);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray(),
            );
            $form->setData($post);

            if ($form->isValid()) {
                if ($this->organService->updateOrganInformation(
                    $organInformation,
                    $form->getData(FormInterface::VALUES_AS_ARRAY),
                )) {
                    return $this->redirect()->toUrl($this->url()->fromRoute('admin_organ'));
                }
            }
        }

        return new ViewModel(
            [
                'form' => $form,
            ]
        );
    }
}
