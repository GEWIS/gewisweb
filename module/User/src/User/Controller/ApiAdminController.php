<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController,
    Zend\View\Model\ViewModel;

class ApiAdminController extends AbstractActionController
{

    /**
     * API token view.
     *
     * Show all API tokens
     */
    public function indexAction()
    {
        return new ViewModel([
            'tokens' => $this->getApiUserService()->getTokens()
        ]);
    }

    /**
     * Add an API token.
     */
    public function addAction()
    {
        $service = $this->getApiUserService();
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
    }

    /**
     * Get the API user service.
     *
     * @return User\Service\ApiUser
     */
    protected function getApiUserService()
    {
        return $this->getServiceLocator()->get('user_service_apiuser');
    }
}
