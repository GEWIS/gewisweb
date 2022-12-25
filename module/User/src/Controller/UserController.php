<?php

namespace User\Controller;

use Laminas\Http\{
    Request,
    Response,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Form\{
    CompanyUserLogin as CompanyLoginForm,
    Login as LoginForm,
};
use User\Service\AclService;
use User\Service\User as UserService;

class UserController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly UserService $userService,
    ) {
    }

    /**
     * User login action.
     */
    public function loginAction(): Response|ViewModel
    {
        if (null !== $this->aclService->getIdentity()) {
            return $this->redirect()->toRoute('home');
        }

        $userType = $this->params()->fromRoute('user_type');
        $referer = $this->getRequest()->getServer('HTTP_REFERER');
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ('company' === $userType) {
                $form = $this->userService->getCompanyUserLoginForm();
            } else {
                $form = $this->userService->getUserLoginForm();
            }

            $form->setData($request->getPost()->toArray());
            if ($form->isValid()) {
                $data = $form->getData();

                if ('company' === $userType) {
                    $login = $this->userService->companyLogin($data);
                } else {
                    $login = $this->userService->userLogin($data);
                }

                if (null !== $login) {
                    if (empty($data['redirect'])) {
                        return $this->redirect()->toUrl($referer);
                    }

                    return $this->redirect()->toUrl($data['redirect']);
                }
            }
        }

        return new ViewModel(
            [
                'form' => $this->handleRedirect($userType, $referer),
                'userType' => $userType,
            ]
        );
    }

    /**
     * @param string $userType
     * @param string|null $referer
     *
     * @return CompanyLoginForm|LoginForm
     */
    private function handleRedirect(
        string $userType,
        ?string $referer,
    ): CompanyLoginForm|LoginForm {
        if ('company' === $userType) {
            $form = $this->userService->getCompanyUserLoginForm();
        } else {
            $form = $this->userService->getUserLoginForm();
        }

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
    public function changePasswordAction(): ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $form = $this->userService->getPasswordForm();

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ($this->userService->changePassword($form->getData())) {
                    return new ViewModel(['success' => true]);
                }
            }
        }

        return new ViewModel(
            [
                'form' => $form,
            ]
        );
    }

    /**
     * Action to reset password.
     */
    public function resetPasswordAction(): ViewModel
    {
        $userType = $this->params()->fromRoute('user_type');
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ('company' === $userType) {
                // TODO: Company password reset.
            } else {
                $form = $this->userService->getResetForm();
            }

            $form->setData($request->getPost()->toArray());
            if ($form->isValid()) {
                $data = $form->getData();

                if ('company' === $userType) {
                    // TODO: Company password reset.
                } else {
                    $this->userService->reset($data);
                }

                // To prevent account enumeration never say whether the e-mail address was (in)correct. Ideally, we
                // delay the responses as there is guaranteed to be a (small) difference in response time depending on
                //whether the user really exists.
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
        $userType = $this->params()->fromRoute('user_type');
        $code = (string) $this->params()->fromRoute('code');

        // get the new user
        // TODO: Company activation and reset
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
