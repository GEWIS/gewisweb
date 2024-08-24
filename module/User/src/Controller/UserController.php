<?php

declare(strict_types=1);

namespace User\Controller;

use DateInterval;
use DateTime;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;
use User\Service\AclService;
use User\Service\User as UserService;

use function base64_decode;
use function base64_encode;
use function str_starts_with;

/**
 * @method FlashMessenger flashMessenger()
 */
class UserController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
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
        $redirectTo = $this->params()->fromQuery('redirect_to');

        if ('company' === $userType) {
            $form = $this->userService->getCompanyUserLoginForm();
        } else {
            $form = $this->userService->getUserLoginForm();
        }

        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                $data = $form->getData();

                if ('company' === $userType) {
                    $login = $this->userService->companyLogin($data);
                } else {
                    $login = $this->userService->userLogin($data);
                }

                if (null !== $login) {
                    return $this->redirect()->toUrl(
                        $this->decodeRedirect($redirectTo),
                    );
                }
            }
        }

        if (null === $redirectTo) {
            $redirectTo = base64_encode(
                $this->url()->fromRoute(
                    route: 'home',
                    options: ['force_canonical' => true],
                ),
            );
        }

        return new ViewModel(
            [
                'form' => $form,
                'redirectTo' => $redirectTo,
                'userType' => $userType,
            ],
        );
    }

    /**
     * Decode the base64 encoded referer, if it is not valid always return the home page.
     */
    private function decodeRedirect(?string $redirectTo): string
    {
        if (null !== $redirectTo) {
            if (false !== ($url = base64_decode($redirectTo))) {
                if (str_starts_with($url, $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://' . $_SERVER['HTTP_HOST'] . '/')) {
                    return $url;
                }
            }
        }

        return $this->url()->fromRoute(
            route: 'home',
            options: ['force_canonical' => true],
        );
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
     * Action to change password.
     */
    public function changePasswordAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('password_change', 'user')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to change passwords'),
            );
        }

        $userType = $this->params()->fromRoute('user_type');
        $form = $this->userService->getPasswordForm($userType);

        /** @var Request $request */
        $request = $this->getRequest();
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
                'userType' => $userType,
            ],
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

        if ('company' === $userType) {
            $form = $this->userService->getCompanyUserResetForm();
        } else {
            $form = $this->userService->getUserResetForm();
        }

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ('company' === $userType) {
                    $this->userService->resetCompany($form->getData());
                } else {
                    $this->userService->resetMember($form->getData());
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
                'userType' => $userType,
            ],
        );
    }

    /**
     * User activation action.
     */
    public function activateAction(): Response|ViewModel
    {
        $userType = $this->params()->fromRoute('user_type');
        $code = (string) $this->params()->fromRoute('code');

        /** @var Request $request */
        $request = $this->getRequest();

        // Handle request to obtain activation information.
        if (
            'member' === $userType
            && '' === $code
        ) {
            if ($request->isPost()) {
                $newUser = $this->userService->register($request->getPost()->toArray());

                if (null !== $newUser) {
                    return new ViewModel(['registered' => true]);
                }
            }

            // show form
            return new ViewModel(
                [
                    'registerForm' => $this->userService->getRegisterForm(),
                ],
            );
        }

        // Handle actual activation of accounts.
        if ('company' === $userType) {
            $newUser = $this->userService->getNewCompanyUser($code);
        } else {
            $newUser = $this->userService->getNewUser($code);
        }

        if (null === $newUser) {
            return $this->redirect()->toRoute('home');
        }

        // Links are only valid for 24 hours.
        if (((new DateTime('now'))->sub(new DateInterval('P1D'))) >= $newUser->getTime()) {
            $this->userService->removeActivation($newUser);

            return $this->redirect()->toRoute('home');
        }

        $form = $this->userService->getActivateForm($userType);

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ($this->userService->activate($form->getData(), $newUser, $userType)) {
                    return new ViewModel(
                        [
                            'activated' => true,
                        ],
                    );
                }
            }
        }

        return new ViewModel(
            [
                'form' => $form,
                'user' => $newUser,
                'userType' => $userType,
            ],
        );
    }
}
