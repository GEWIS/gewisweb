<?php

namespace User\Controller;

use User\Service\User;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;

class UserController extends AbstractActionController
{

    /**
     * @var User
     */
    private $userService;

    public function __construct(User $userService)
    {
        $this->userService = $userService;
    }

    /**
     * User login action.
     */
    public function indexAction()
    {
        $referer = $this->getRequest()->getServer('HTTP_REFERER');

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            // try to login
            $login = $this->userService->login($data);
            if (!is_null($login)) {
                if (is_null($data['redirect']) || empty($data['redirect'])) {
                    return $this->redirect()->toUrl($referer);
                }
                return $this->redirect()->toUrl($data['redirect']);
            }
        }

        $form = $this->handleRedirect($this->userService, $referer);

        return new ViewModel(
            [
            'form' => $form
            ]
        );
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
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            // try to login
            $login = $this->userService->pinLogin($data);

            if (null !== $login) {
                return new JsonModel(
                    [
                    'login' => true,
                    'user' => $login->toArray()
                    ]
                );
            }
        }
        return new JsonModel(
            [
            'login' => false
            ]
        );
    }

    /**
     * User logout action.
     */
    public function logoutAction()
    {
        $this->userService->logout();

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
        if ($this->getRequest()->isPost()) {
            $newUser = $this->userService->register($this->getRequest()->getPost());
            if (null !== $newUser) {
                return new ViewModel(
                    [
                    'registered' => true,
                    'user' => $newUser
                    ]
                );
            }
        }

        // show form
        return new ViewModel(
            [
            'form' => $this->userService->getRegisterForm()
            ]
        );
    }

    /**
     * Action to change password.
     */
    public function passwordAction()
    {
        $request = $this->getRequest();

        if ($request->isPost() && $this->userService->changePassword($request->getPost())) {
            return new ViewModel(
                [
                'success' => true
                ]
            );
        }

        return new ViewModel(
            [
            'form' => $this->userService->getPasswordForm()
            ]
        );
    }

    /**
     * Action to reset password.
     */
    public function resetAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $newUser = $this->userService->reset($request->getPost());
            if (null !== $newUser) {
                return new ViewModel(
                    [
                    'reset' => true,
                    'user' => $newUser
                    ]
                );
            }
        }

        return new ViewModel(
            [
            'form' => $this->userService->getPasswordForm()
            ]
        );
    }

    /**
     * User activation action.
     */
    public function activateAction()
    {
        $code = $this->params()->fromRoute('code');

        if (empty($code)) {
            // no code given
            return $this->redirect()->toRoute('home');
        }

        // get the new user
        $newUser = $this->userService->getNewUser($code);

        if (null === $newUser) {
            return $this->redirect()->toRoute('home');
        }

        if ($this->getRequest()->isPost() && $this->userService->activate($this->getRequest()->getPost(), $newUser)) {
            return new ViewModel(
                [
                'activated' => true
                ]
            );
        }

        return new ViewModel(
            [
            'form' => $this->userService->getActivateForm(),
            'user' => $newUser
            ]
        );
    }
}
