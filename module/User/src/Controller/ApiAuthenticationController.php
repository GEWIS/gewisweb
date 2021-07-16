<?php

namespace User\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use User\Model\User;
use User\Permissions\NotAllowedException;
use User\Service\AclService;
use User\Service\ApiApp;
use User\Service\User as UserService;

class ApiAuthenticationController extends AbstractActionController
{
    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var ApiApp
     */
    protected $apiAppService;
    private AclService $aclService;

    /**
     * ApiAuthenticationController constructor.
     */
    public function __construct(UserService $userService, ApiApp $apiAppService, AclService $aclService)
    {
        $this->userService = $userService;
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
            $this->getApiAppService()->callbackWithToken($appId, $identity)
        );
    }

    /**
     * @return UserService
     */
    public function getUserService()
    {
        return $this->userService;
    }

    /**
     * @return ApiApp
     */
    public function getApiAppService()
    {
        return $this->apiAppService;
    }
}
