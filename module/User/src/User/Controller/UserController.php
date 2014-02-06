<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController,
    Zend\View\Model\ViewModel;

class UserController extends AbstractActionController
{

    public function indexAction()
    {
        $userService = $this->getUserService();

        if ($this->getRequest()->isPost()) {
            // try to login
            $login = $userService->login($this->getRequest()->getPost());

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

    /**
     * Get a user service.
     *
     * @return User\Service\User
     */
    protected function getUserService()
    {
        return $this->getServiceLocator()->get('user_service_user');
    }
}
