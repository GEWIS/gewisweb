<?php

namespace User\Controller;

use User\Service\ApiUser;
use User\Service\User;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ApiAdminController extends AbstractActionController
{

    /**
     * @var ApiUser
     */
    private $apiUserService;

    public function __construct(User $apiUserService)
    {
        $this->apiUserService = $apiUserService;
    }

    /**
     * API token view.
     *
     * Show all API tokens
     */
    public function indexAction()
    {
        return new ViewModel([
            'tokens' => $this->apiUserService->getTokens()
        ]);
    }

    /**
     * Add an API token.
     */
    public function addAction()
    {
        $service = $this->apiUserService;
        $request = $this->getRequest();

        if ($request->isPost()) {
            $apiUser = $service->addToken($request->getPost());

            if (null !== $apiUser) {
                return new ViewModel([
                    'apiUser' => $apiUser
                ]);
            }
        }

        return new ViewModel([
            'form' => $service->getApiTokenForm()
        ]);
    }

    /**
     * Remove an API token.
     */
    public function removeAction()
    {
        $id = $this->params()->fromRoute('id');
        $service = $this->apiUserService;
        $request = $this->getRequest();

        if ($request->isPost()) {
            // remove the token and redirect
            $service->removeToken($id);

            return $this->redirect()->toRoute('user_admin/api');
        }

        return new ViewModel([
            'token' => $service->getToken($id)
        ]);
    }
}
