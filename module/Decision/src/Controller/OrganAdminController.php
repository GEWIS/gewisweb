<?php

declare(strict_types=1);

namespace Decision\Controller;

use Decision\Service\Organ as OrganService;
use Laminas\Form\FormInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Override;

use function array_merge_recursive;

class OrganAdminController extends AbstractActionController
{
    public function __construct(private readonly OrganService $organService)
    {
    }

    /**
     * Index action, shows all active organs.
     */
    #[Override]
    public function indexAction(): ViewModel
    {
        return new ViewModel(
            [
                'organs' => $this->organService->getEditableOrgans(),
            ],
        );
    }

    /**
     * Show an organ.
     */
    public function editAction(): Response|ViewModel
    {
        $organId = (int) $this->params()->fromRoute('organ_id');
        $organInformation = $this->organService->getEditableOrganInformation($organId);

        if (false === $organInformation) {
            return $this->notFoundAction();
        }

        $form = $this->organService->getOrganInformationForm($organInformation);

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray(),
            );
            $form->setData($post);

            if ($form->isValid()) {
                if (
                    $this->organService->updateOrganInformation(
                        $organInformation,
                        $form->getData(FormInterface::VALUES_AS_ARRAY),
                    )
                ) {
                    return $this->redirect()->toUrl($this->url()->fromRoute('admin_organ'));
                }
            }
        }

        return new ViewModel(
            [
                'form' => $form,
            ],
        );
    }
}
