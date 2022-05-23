<?php

namespace User\Controller;

use DateTime;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
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
    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var ApiAppService
     */
    protected ApiAppService $apiAppService;

    protected ApiAppAuthenticationMapper $apiAppAuthenticationMapper;

    protected ApiAppMapper $apiAppMapper;

    /**
     * ApiAuthenticationController constructor.
     *
     * @param AclService $aclService
     * @param ApiAppService $apiAppService
     * @param ApiAppMapper $apiAppMapper
     */
    public function __construct(
        AclService $aclService,
        ApiAppService $apiAppService,
        ApiAppAuthenticationMapper $apiAppAuthenticationMapper,
        ApiAppMapper $apiAppMapper,
    ) {
        $this->aclService = $aclService;
        $this->apiAppService = $apiAppService;
        $this->apiAppAuthenticationMapper = $apiAppAuthenticationMapper;
        $this->apiAppMapper = $apiAppMapper;
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

        $request = $this->getRequest();
        if ($request->isPost()) {
            // Check against `cancel` such that we can work with confirm and continue.
            if (null === $request->getPost('cancel')) {
                return $this->redirect()->toUrl(
                    $this->apiAppService->callbackWithToken($app, $identity)
                );
            } else {
                // If the user does not want to continue, let them navigate back to the application.
                return $this->redirect()->toUrl($app->getUrl());
            }
        }

        // If the user has previously authenticated with the external application, but it has been longer than 3 months
        // show a small notice as a reminder. Otherwise, directly authenticate.
        $remind = false;
        if (null !== ($lastAuthentication = $this->apiAppAuthenticationMapper->getLastAuthentication($identity, $app))) {
            if (90 < (new DateTime('now'))->diff($lastAuthentication->getTime())->days) {
                $remind = true;
            } else {
                return $this->redirect()->toUrl(
                    $this->apiAppService->callbackWithToken($app, $identity)
                );
            }
        }

        return new ViewModel([
            'app' => $appId,
            'claims' => $app->getClaims(),
            'remind' => $remind,
        ]);
    }
}
