<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController,
    Zend\View\Model\ViewModel;

class UserController extends AbstractActionController
{

    /**
     * User login action.
     */
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
     * User logout action.
     */
    public function logoutAction()
    {
        $userService = $this->getUserService();

        if ($this->getRequest()->isPost()) {
            if ($userService->logout($this->getRequest()->getPost())) {
                return new ViewModel(array(
                    'logout' => true
                ));
            }
            // when the user is not logged out, return the user to the homepage
            return $this->redirect()->toRoute('home');
        }

        // show form
        return new ViewModel(array(
            'form' => $userService->getLogoutform()
        ));
    }

    /**
     * User register action.
     */
    public function registerAction()
    {
        $userService = $this->getUserService();

        if ($this->getRequest()->isPost()) {
            // TODO: register the user
        }

        // show form
        return new ViewModel(array(
            'form' => $userService->getRegisterForm()
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
