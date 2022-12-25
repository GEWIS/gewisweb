<?php

namespace Company\Controller;

use Company\Service\AclService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

class CompanyAccountController extends AbstractActionController
{
    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * CompanyAccountController constructor.
     *
     * @param AclService $aclService
     * @param Translator $translator
     */
    public function __construct(
        AclService $aclService,
        Translator $translator,
    ) {
        $this->aclService = $aclService;
        $this->translator = $translator;
    }

    public function selfAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('viewAccount', 'company')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the company accounts')
            );
        }

        return new ViewModel([]);
    }

    public function settingsAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('viewAccount', 'company')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the company accounts')
            );
        }

        return new ViewModel([]);
    }

    public function jobsAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('viewAccount', 'company')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the company accounts')
            );
        }

        return new ViewModel([]);
    }

    public function addJobAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('createOwn', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create jobs')
            );
        }

        return new ViewModel([]);
    }

    public function editJobAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('editOwn', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit jobs')
            );
        }

        return new ViewModel([]);
    }

    public function deleteJobAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('deleteOwn', 'job')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete jobs')
            );
        }

        return new ViewModel([]);
    }
}
