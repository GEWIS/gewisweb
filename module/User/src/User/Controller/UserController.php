<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController,
    Zend\View\Model\ViewModel;

class UserController extends AbstractActionController
{

    public function indexAction()
    {
        $userService = $this->getServiceLocator()->get('user_service_user');
        return new ViewModel(array(
            'form' => $userService->getLoginForm()
        ));
    }
}
