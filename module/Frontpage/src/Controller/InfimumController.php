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
    /**
     * @var InfimumService
     */
    private InfimumService $infimumService;

    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * FrontpageController constructor.
     *
     * @param InfimumService $infimumService
     * @param AclService $aclService
     * @param Translator $translator
     */
    public function __construct(
        InfimumService $infimumService,
        AclService $aclService,
        Translator $translator,
    ) {
        $this->infimumService = $infimumService;
        $this->aclService = $aclService;
        $this->translator = $translator;
    }

    public function showAction()
    {
        if (!$this->aclService->isAllowed('view', 'infimum')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view infima'));
        }

        $infimum = $this->infimumService->getInfimum();

        return new JsonModel(variables: ['content' => $infimum]);
    }
}
