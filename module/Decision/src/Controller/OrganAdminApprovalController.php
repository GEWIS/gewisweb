<?php

declare(strict_types=1);

namespace Decision\Controller;

use Decision\Service\AclService;
use Decision\Mapper\OrganInformation as OrganInformationMapper;
use Decision\Service\Organ as OrganService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

/**
 * @method FlashMessenger flashMessenger()
 */
class OrganAdminApprovalController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly OrganInformationMapper $organInformationMapper,
        private readonly OrganService $organService,
    ) {
    }

    public function indexAction(): ViewModel
    {
        if ($this->aclService->isAllowed('approve', 'organInformation')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the approval status of organs'),
            );
        }

        return new ViewModel(
            [
                'organInformations' => $this->organInformationMapper->findProposals(),
            ],
        );
    }

    public function approvalAction(): ViewModel
    {
        // TODO: implement

        return new ViewModel();
    }

    public function changeApprovalStatusAction(): Response|ViewModel
    {
        // TODO: implement

        return new ViewModel();
    }

    public function proposalAction(): ViewModel
    {
        // TODO: implement

        return new ViewModel();
    }

    public function changeProposalStatusAction(): Response|ViewModel
    {
        // TODO: implement

        return new ViewModel();
    }
}
