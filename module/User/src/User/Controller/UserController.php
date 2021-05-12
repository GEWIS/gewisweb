<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class UserController extends AbstractActionController
{
    /**
     * User login action.
     */
    public function indexAction()
    {
        $userService = $this->getUserService();
        $referer = $this->getRequest()->getServer('HTTP_REFERER');

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            // try to login
            $login = $userService->login($data);
            if (!is_null($login)) {
                if (is_null($data['redirect']) || empty($data['redirect'])) {
                    return $this->redirect()->toUrl($referer);
                }
                return $this->redirect()->toUrl($data['redirect']);
            }
        }

        $form = $this->handleRedirect($userService, $referer);

        return new ViewModel([
            'form' => $form
        ]);
    }

    private function handleRedirect($userService, $referer)
    {
        $form = $userService->getLoginForm();
        if (is_null($form->get('redirect')->getValue())) {
            $redirect = $this->getRequest()->getQuery('redirect');
            if (isset($redirect)) {
                $form->get('redirect')->setValue($redirect);
                return $form;
            }
            if (isset($referer)) {
                $form->get('redirect')->setValue($referer);
                return $form;
            }
            $form->get('redirect')->setValue($this->url()->fromRoute('home'));
        }
        return $form;
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
     * User logout action.
     */
    public function companyLogoutAction()
    {
        $this->getCompanyService()->logout();

        // used to get point user to current page on logout, only use
//        if (isset($_SERVER['HTTP_REFERER'])) {
//            return $this->redirect()->toUrl($_SERVER['HTTP_REFERER']);
//        }

        // default set to home
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
        $userService = $this->getUserService();
        $request = $this->getRequest();

        if ($request->isPost() && $userService->changePassword($request->getPost())) {
            return new ViewModel([
                'success' => true
            ]);
        }

        return new ViewModel([
            'form' => $this->getUserService()->getPasswordForm()
        ]);
    }

    /**
     * Action to reset password.
     */
    public function resetAction()
    {
        $userService = $this->getUserService();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $newUser = $userService->reset($request->getPost());
            if (null !== $newUser) {
                return new ViewModel([
                    'reset' => true,
                    'user' => $newUser
                ]);
            }
        }

        return new ViewModel([
            'form' => $userService->getPasswordResetForm()
        ]);
    }

    public function resetCompanyAction()
    {
        $userService = $this->getUserService();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $newUser = $userService->resetCompany($request->getPost());
            if (null !== $newUser) {
                return new ViewModel([
                    'reset' => true,
                    'user' => $newUser
                ]);
            }
        }

        return new ViewModel([
            'form' => $userService->getPasswordResetForm()
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
     * Comapny activation action.
     */
    // TODO: commments
    public function activateCompanyAction()
    {
        $userService = $this->getUserService();

        $code = $this->params()->fromRoute('code');

        if (empty($code)) {
            // no code given
            return $this->redirect()->toRoute('home');
        }

        // get the new company
        $newCompany = $userService->getNewCompany($code);

        if (null === $newCompany) {
            return $this->redirect()->toRoute('home');
        }

        if ($this->getRequest()->isPost() && $userService->activateCompany($this->getRequest()->getPost(), $newCompany)) {
            return new ViewModel([
                'activated' => true
            ]);
        }

        return new ViewModel([
            'form' => $userService->getActivateForm(),
            'company' => $newCompany
        ]);
    }

    public function companyAction()
    {
        $companyService = $this->getCompanyService();

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            // try to login
            $login = $companyService->companyLogin($data);

            if (!is_null($login)) {
                return $this->redirect()->toRoute('companyaccount/index');
            }
        }


        return new ViewModel([
            'form' => $companyService->getCompanyLoginForm()
        ]);
    }


    /**
     * Get a user service.
     *
     * @return \User\Service\Company
     */
    protected function getCompanyService()
    {
        return $this->getServiceLocator()->get('user_service_company');
    }

    /**
     * Get a user service.
     *
     * @return \User\Service\User
     */
    protected function getUserService()
    {
        return $this->getServiceLocator()->get('user_service_user');
    }
}
