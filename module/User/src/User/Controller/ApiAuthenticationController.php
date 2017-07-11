<?php

namespace User\Controller;


use User\Service\User as UserService;
use Zend\Mvc\Controller\AbstractActionController;

class ApiAuthenticationController extends AbstractActionController
{

    /**
     * @var UserService
     */
    protected $userService;


    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function tokenAction()
    {
        $appId = $this->params()->fromRoute('appId');
        var_dump($appId);
        $identity = $this->getUserService()->getIdentity();
    }

    /**
     * @return UserService
     */
    public function getUserService()
    {
        return $this->userService;
    }
}