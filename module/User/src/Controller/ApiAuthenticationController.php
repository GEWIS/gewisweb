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
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var ApiAppService
     */
    protected ApiAppService $apiAppService;

    /**
     * ApiAuthenticationController constructor.
     *
     * @param AclService $aclService
     * @param ApiAppService $apiAppService
     */
    public function __construct(
        AclService $aclService,
        ApiAppService $apiAppService,
    ) {
        $this->aclService = $aclService;
        $this->apiAppService = $apiAppService;
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
