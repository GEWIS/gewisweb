<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController,
    Zend\View\Model\ViewModel,
    Zend\View\Model\JsonModel;

class UserController extends AbstractActionController
{

    /**
     * User login action.
     */
    public function indexAction()
    {
        $userService = $this->getUserService();

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            // try to login
            $login = $userService->login($data);

            if (null !== $login) {
                $this->redirect()->toUrl($data['redirect']);

                return new ViewModel([
                    'login' => true
                ]);
            }
        }

        // show form
        $form = $userService->getLoginForm();
        if(is_null($form->get('redirect')->getValue())) {
            if (isset($_SERVER['HTTP_REFERER'])) {
                $form->get('redirect')->setValue($_SERVER['HTTP_REFERER']);
            } else {
                $form->get('redirect')->setValue($this->url()->fromRoute('home'));
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }

    public function pinLoginAction()
    {
        $userService = $this->getUserService();

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            // try to login
            $login = $userService->pinLogin($data);

            if (null !== $login) {

                return new JsonModel([
                    'login' => true,
                    'user' => $login->toArray()
                ]);
            }
        }
        return new JsonModel([
            'login' => false
        ]);
    }
    /**
     * User logout action.
     */
    public function logoutAction()
    {
        $this->getUserService()->logout();

        if (isset($_SERVER['HTTP_REFERER'])) {
            return $this->redirect()->toUrl($_SERVER['HTTP_REFERER']);
        }

        return $this->redirect()->toRoute('home');
    }

    /**
     * User register action.
     */
    public function registerAction()
    {
        $userService = $this->getUserService();

        if ($this->getRequest()->isPost()) {
            $newUser = $userService->register($this->getRequest()->getPost());
            if (null !== $newUser) {
                return new ViewModel([
                    'registered' => true,
                    'user' => $newUser
                ]);
            }
        }

        // show form
        return new ViewModel([
            'form' => $userService->getRegisterForm()
        ]);
    }

    /**
     * Action to change password.
     */
    public function passwordAction()
    {
        return new ViewModel([
            'form' => $this->getUserService()->getPasswordForm()
        ]);
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
            return new ViewModel([
                'activated' => true
            ]);
        }

        return new ViewModel([
            'form' => $userService->getActivateForm(),
            'user' => $newUser
        ]);
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
