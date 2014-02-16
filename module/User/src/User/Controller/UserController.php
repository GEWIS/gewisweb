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
            $userService->register($this->getRequest()->getPost());
        }

        // show form
        return new ViewModel(array(
            'form' => $userService->getRegisterForm()
        ));
    }

    /**
     * User activation action.
     */
    public function activateAction()
    {
        $userService = $this->getUserService();

        $code = $this->params()->fromRoute('code');

        if (empty($code)) {
            // no code given
            return $this->redirect()->toRoute('home');
        }

        // get the new user
        $newUser = $userService->getNewUser($code);

        if (null === $newUser) {
            return $this->redirect()->toRoute('home');
        }

        if ($this->getRequest()->isPost() && $userService->activate($this->getRequest()->getPost(), $newUser)) {
            return new ViewModel(array(
                'activated' => true
            ));
        }

        return new ViewModel(array(
            'form' => $userService->getActivateForm(),
            'user' => $newUser
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
