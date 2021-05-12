<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

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
        $id = $this->params()->fromRoute('id');
        $service = $this->getApiUserService();
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
