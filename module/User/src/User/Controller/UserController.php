<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController,
    Zend\View\Model\ViewModel;

class UserController extends AbstractActionController
{

    public function indexAction()
    {
        $userService = $this->getServiceLocator()->get('user_service_user');

        if ($this->getRequest()->isPost()) {
            $login = $userService->login($this->getRequest()->getPost());

            // try to login
            if (null !== $login) {
                return new ViewModel(array(
                    'login' => true
                ));
            }
        }

        // show form
        return new ViewModel(array(
            'form' => $userService->getLoginForm()
        ));
    }
}
