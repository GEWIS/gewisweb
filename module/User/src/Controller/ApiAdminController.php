<?php

namespace User\Controller;

use Laminas\Http\{
    Request,
    Response,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Service\ApiUser as ApiUserService;

class ApiAdminController extends AbstractActionController
{
    /**
     * @var ApiUserService
     */
    private ApiUserService $apiUserService;

    /**
     * ApiAdminController constructor.
     *
     * @param ApiUserService $apiUserService
     */
    public function __construct(ApiUserService $apiUserService)
    {
        $this->apiUserService = $apiUserService;
    }

    /**
     * API token view.
     *
     * Show all API tokens
     */
    public function indexAction(): ViewModel
    {
        return new ViewModel(
            [
                'tokens' => $this->apiUserService->getTokens(),
            ]
        );
    }

    /**
     * Add an API token.
     */
    public function addAction(): ViewModel
    {
        $form = $this->apiUserService->getApiTokenForm();

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                $apiUser = $this->apiUserService->addToken($form->getData());

                if (null !== $apiUser) {
                    return new ViewModel(
                        [
                            'apiUser' => $apiUser,
                        ]
                    );
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
     * Remove an API token.
     */
    public function removeAction(): Response|ViewModel
    {
        $id = $this->params()->fromRoute('id');
        $service = $this->apiUserService;

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            // remove the token and redirect
            $service->removeToken($id);

            return $this->redirect()->toRoute('user_admin/api');
        }

        return new ViewModel(
            [
                'token' => $service->getToken($id),
            ]
        );
    }
}
