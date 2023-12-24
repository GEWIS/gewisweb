<?php

declare(strict_types=1);

namespace Decision\Controller;

use Decision\Service\AclService;
use Decision\Service\Gdpr as GdprService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\JsonModel;
use User\Permissions\NotAllowedException;

class AdminMemberController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly GdprService $gdprService,
    ) {
    }

    public function memberGdprAction(): JsonModel
    {
        if (!$this->aclService->isAllowed('export', 'gdpr')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to export member data'),
            );
        }

        return new JsonModel($this->gdprService->getMemberData((int) $this->params()->fromRoute('lidnr')));
    }
}
