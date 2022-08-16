<?php

namespace Frontpage\Controller;

use Application\Service\Infimum as InfimumService;
use Frontpage\Service\AclService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\JsonModel;
use User\Permissions\NotAllowedException;

class InfimumController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly InfimumService $infimumService,
    ) {
    }

    public function showAction(): JsonModel
    {
        if (!$this->aclService->isAllowed('view', 'infimum')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view infima'));
        }

        $infimum = $this->infimumService->getInfimum();

        return new JsonModel(variables: ['content' => $infimum]);
    }
}
