<?php

namespace Company\Controller;

use Company\Service\AclService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

class CompanyAccountController extends AbstractActionController
{
    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * CompanyAccountController constructor.
     *
     * @param AclService $aclService
     */
    public function __construct(AclService $aclService) {
        $this->aclService = $aclService;
    }

    public function selfAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('viewAccount', 'company')) {
            throw new NotAllowedException(
                'You are not allowed to view the company accounts'
            );
        }

        return new ViewModel([]);
    }
}
