<?php

namespace User\Controller;

use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Mapper\ApiApp as ApiAppMapper;
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
        ApiAppMapper $apiAppMapper,
    ) {
        $this->aclService = $aclService;
        $this->apiAppService = $apiAppService;
        $this->apiAppMapper = $apiAppMapper;
    }

    public function tokenAction(): Response|ViewModel
    {
        $appId = $this->params()->fromRoute('appId');
        $app = $this->apiAppMapper->findByAppId($appId);

        if (null === $app) {
            return $this->notFoundAction();
        }

        $identity = $this->aclService->getIdentity();

        if (!$identity instanceof User) {
            throw new NotAllowedException('User not fully authenticated.');
        }

        return $this->redirect()->toUrl(
            $this->apiAppService->callbackWithToken($app, $identity->getMember())
        );
    }
}
