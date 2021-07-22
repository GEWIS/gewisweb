<?php

namespace User\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use User\Model\User;
use User\Permissions\NotAllowedException;
use User\Service\{
    AclService,
    ApiApp as ApiAppService,
};

class ApiAuthenticationController extends AbstractActionController
{
    /**
     * @var ApiAppService
     */
    protected ApiAppService $apiAppService;

    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * ApiAuthenticationController constructor.
     *
     * @param ApiAppService $apiAppService
     * @param AclService $aclService
     */
    public function __construct(
        ApiAppService $apiAppService,
        AclService $aclService
    ) {
        $this->apiAppService = $apiAppService;
        $this->aclService = $aclService;
    }

    public function tokenAction()
    {
        $appId = $this->params()->fromRoute('appId');
        $identity = $this->aclService->getIdentity();

        if (!$identity instanceof User) {
            throw new NotAllowedException('User not fully authenticated.');
        }

        return $this->redirect()->toUrl(
            $this->apiAppService->callbackWithToken($appId, $identity)
        );
    }
}
