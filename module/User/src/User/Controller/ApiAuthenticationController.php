<?php

namespace User\Controller;


use User\Model\User;
use User\Permissions\NotAllowedException;
use User\Service\ApiApp;
use User\Service\User as UserService;
use Zend\Mvc\Controller\AbstractActionController;

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


    /**
     * ApiAuthenticationController constructor.
     * @param UserService $userService
     * @param ApiApp $apiAppService
     */
    public function __construct(UserService $userService, ApiApp $apiAppService)
    {
        $this->userService = $userService;
        $this->apiAppService = $apiAppService;
    }

    public function tokenAction()
    {
        $appId = $this->params()->fromRoute('appId');
        $identity = $this->getUserService()->getIdentity();

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