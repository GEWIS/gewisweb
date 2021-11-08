<?php

namespace Activity\Controller;

use Activity\Service\AclService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

class AdminOptionController extends AbstractActionController
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
     * AdminOptionController constructor.
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

    public function indexAction()
    {
        if (!$this->aclService->isAllowed('view', 'activity_calendar_period')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to administer option calendar periods'));
        }

        return new ViewModel();
    }

    public function addAction()
    {
        if (!$this->aclService->isAllowed('create', 'activity_calendar_period')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create option calendar periods'));
        }

        return new ViewModel();
    }

    public function deleteAction()
    {
        if (!$this->aclService->isAllowed('delete', 'activity_calendar_period')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete option calendar periods'));
        }

        return new ViewModel();
    }

    public function editAction()
    {
        if (!$this->aclService->isAllowed('edit', 'activity_calendar_period')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit option calendar periods'));
        }

        return new ViewModel();
    }
}
