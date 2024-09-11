<?php

declare(strict_types=1);

namespace Decision\Controller;

use Decision\Model\OrganInformation as OrganInformationModel;
use Decision\Service\AclService;
use Decision\Service\Organ as OrganService;
use Laminas\Form\FormInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

use function array_merge_recursive;

class OrganAdminController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly OrganService $organService,
    ) {
    }

    /**
     * Index action, shows all active organs filtered for the user.
     */
    public function indexAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('view', 'decision_organ_admin')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to administer organs'),
            );
        }

        return new ViewModel(
            [
                'organs' => $this->organService->getEditableOrgans(),
            ],
        );
    }

    /**
     * Route to create/propose a new {@link OrganInformationModel} iff none exists.
     */
    public function createAction(): Response|ViewModel
    {
        $organId = (int) $this->params()->fromRoute('organ_id');
        $organ = $this->organService->getOrgan($organId);

        if (null === $organ) {
            return $this->notFoundAction();
        }

        if (!$this->organService->canUseOrgan($organ)) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create organ information for this organ'),
            );
        }

        $form = $this->organService->getOrganInformationForm();

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray(),
            );
            $form->setData($post);

            if ($form->isValid()) {
                if (null !== ($organInformation = $this->organService->createOrganInformation($organ, $post))) {
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

    /**
     * Route to propose a change to an {@link OrganInformationModel}.
     */
    public function editAction(): Response|ViewModel
    {
        $organId = (int) $this->params()->fromRoute('organ_id');
        $organ = $this->organService->getOrgan($organId);

        if (null === $organ) {
            return $this->notFoundAction();
        }

        $organInformation = $this->organService->getEditableOrganInformation($organ);

        if (false === $organInformation) {
            return $this->notFoundAction();
        }

        if (!$this->aclService->isAllowed('edit', $organInformation)) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit organ information'),
            );
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
                'organ' => $organ,
            ],
        );
    }
}
