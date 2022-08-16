<?php

namespace User\Controller;

use DateTime;
use Laminas\Http\{
    Request,
    Response,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Form\{
    ApiAppAuthorisation as ApiAppAuthorisationInitialForm,
    ApiAppAuthorisation as ApiAppAuthorisationReminderForm,
};
use User\Mapper\{
    ApiApp as ApiAppMapper,
    ApiAppAuthentication as ApiAppAuthenticationMapper,
};
use User\Model\User;
use User\Permissions\NotAllowedException;
use User\Service\{
    AclService,
    ApiApp as ApiAppService,
};

class ApiAuthenticationController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly ApiAppService $apiAppService,
        private readonly ApiAppAuthenticationMapper $apiAppAuthenticationMapper,
        private readonly ApiAppMapper $apiAppMapper,
        private readonly ApiAppAuthorisationInitialForm $apiAppAuthorisationInitialForm,
        private readonly ApiAppAuthorisationReminderForm $apiAppAuthorisationReminderForm,
    ) {
    }

    public function tokenAction(): Response|ViewModel
    {
        $identity = $this->aclService->getIdentity();

        if (!$identity instanceof User) {
            throw new NotAllowedException('User not fully authenticated.');
        }

        $appId = $this->params()->fromRoute('appId');
        $app = $this->apiAppMapper->findByAppId($appId);

        if (null === $app) {
            return $this->notFoundAction();
        }

        // If the user has previously authenticated with the external application, but it has been longer than 3 months
        // show a small notice as a reminder. Otherwise, directly authenticate.
        $remind = false;
        if (null !== ($lastAuthentication = $this->apiAppAuthenticationMapper->getLastAuthentication($identity, $app))) {
            if (90 < (new DateTime('now'))->diff($lastAuthentication->getTime())->days) {
                $remind = true;
            } else {
                // Again, make sure that we do not use `Location: ` based redirects.
                $viewModel = (new ViewModel())->setTemplate('user_token/redirect');

                return $viewModel->setVariables(
                    [
                        'app' => $app->getAppId(),
                        'url' => $this->apiAppService->callbackWithToken($app, $identity),
                    ]
                );
            }
        }

        if ($remind) {
            $form = $this->apiAppAuthorisationReminderForm;
        } else {
            $form = $this->apiAppAuthorisationInitialForm;
        }

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                // Check against `cancel` such that we can work with confirm and continue.
                if (null === $form->getData()['cancel']) {
                    $url = $this->apiAppService->callbackWithToken($app, $identity);
                } else {
                    // If the user does not want to continue, let them navigate back to the application.
                    $url = $app->getUrl();
                }

                // Change template to the redirect template, as we cannot use the `redirect` plugin to short-circuit
                // execution of the request. Chromium browsers do not accept a `Location: ` redirect after `POST`ing
                // (CSP violation). Hence, we must return an actual `ViewModel` that will "manually" refresh the page to
                // redirect to the correct URL.
                $viewModel = (new ViewModel())->setTemplate('user_token/redirect');

                return $viewModel->setVariables(
                    [
                        'app' => $app->getAppId(),
                        'url' => $url,
                    ]
                );
            }
        }

        return new ViewModel(
            [
                'app' => $appId,
                'claims' => $app->getClaims(),
                'form' => $form,
                'remind' => $remind,
            ]
        );
    }
}
