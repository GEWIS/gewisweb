<?php

namespace User\Controller;

use Laminas\Http\{
    PhpEnvironment\Request as RequestEnvironment,
    Request,
    Response,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Form\Login as LoginForm;
use User\Service\User as UserService;

class UserController extends AbstractActionController
{
    /**
     * @var UserService
     */
    private UserService $userService;

    /**
     * UserController constructor.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * User login action.
     */
    public function indexAction(): Response|ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $referer = (new RequestEnvironment())->getServer('HTTP_REFERER');

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            // Update the referrer if a redirect is already set.
            if (!empty($data['redirect'])) {
                $referer = $data['redirect'];
            }

            // try to login
            $login = $this->userService->login($data);

            if (null !== $login) {
                return $this->redirect()->toUrl($referer);
            }
        }

        return new ViewModel(
            [
                'form' => $this->handleRedirect($referer),
            ]
        );
    }

    /**
     * @param string|null $referer
     *
     * @return LoginForm
     */
    private function handleRedirect(?string $referer): LoginForm
    {
        $form = $this->userService->getLoginForm();
        if (is_null($form->get('redirect')->getValue())) {
            $redirect = $this->params()->fromQuery('redirect');

            if (isset($redirect)) {
                $form->get('redirect')->setValue($redirect);

                return $form;
            }

            if (null !== $referer) {
                $form->get('redirect')->setValue($referer);

                return $form;
            }

            $form->get('redirect')->setValue($this->url()->fromRoute('home'));
        }

        return $form;
    }

    /**
     * User logout action.
     */
    public function logoutAction(): Response
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
    public function registerAction(): ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $newUser = $this->userService->register($request->getPost()->toArray());

            if (null !== $newUser) {
                return new ViewModel(['registered' => true]);
            }
        }

        // show form
        return new ViewModel(
            [
                'form' => $this->userService->getRegisterForm(),
            ]
        );
    }

    /**
     * Action to change password.
     */
    public function passwordAction(): ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost() && $this->userService->changePassword($request->getPost())) {
            return new ViewModel(
                [
                    'success' => true,
                ]
            );
        }

        return new ViewModel(
            [
                'form' => $this->userService->getPasswordForm(),
            ]
        );
    }

    /**
     * Action to reset password.
     */
    public function resetAction(): ViewModel
    {
        $form = $this->userService->getResetForm();
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                $this->userService->reset($form->getData());

                // To prevent enumeration, always say a password has been reset.
                return new ViewModel(['reset' => true]);
            }
        }

        return new ViewModel(
            [
                'form' => $form,
            ]
        );
    }

    /**
     * User activation action.
     */
    public function activateAction(): Response|ViewModel
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

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($this->userService->activate($request->getPost()->toArray(), $newUser)) {
                return new ViewModel(
                    [
                        'activated' => true,
                    ]
                );
            }
        }

        return new ViewModel(
            [
                'form' => $this->userService->getActivateForm(),
                'user' => $newUser,
            ]
        );
    }
}
