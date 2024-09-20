<?php

declare(strict_types=1);

namespace User\Listener;

use Application\Model\Enums\ApiResponseStatuses;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;
use User\Permissions\NotAuthenticatedException;

final class Authorization
{
    public function __invoke(MvcEvent $e): void
    {
        if (
            'error-exception' !== $e->getError()
            || null === $e->getParam('exception')
            || (
                !($e->getParam('exception') instanceof NotAllowedException)
                && !($e->getParam('exception') instanceof NotAuthenticatedException)
            )
        ) {
            return;
        }

        var_dump("yippie!");
        var_dump($e->getResponse());
        var_dump($e->getResponse());

        /** @var HttpRequest $request */
        $request = $e->getRequest();
        /** @var HttpResponse $response */
        $response = $e->getResponse() ?? new HttpResponse();

        $e->setViewModel((new ViewModel())->setTemplate('layout/layout'));

        if ($e->getParam('exception') instanceof NotAllowedException) {
            if (
                $request instanceof HttpRequest
                && str_starts_with($request->getUri()->getPath(), '/api')
            ) {
                // Handle API request
                $e->setViewModel(new JsonModel([
                    'status' => ApiResponseStatuses::Forbidden,
                    'error' => [
                        'type' => NotAllowedException::class,
                        'exception' => $e->getParam('exception')->getMessage(),
                    ],
                ]));
            } else {
                // Handle non-API request
                $viewModel = new ViewModel();
                $viewModel->setTemplate('production' === APP_ENV ? 'error/403' : 'error/debug/403');
                $e->getViewModel()->addChild($viewModel);
                $response->setStatusCode(HttpResponse::STATUS_CODE_403);

//                $e->stopPropagation();
            }

            $e->setResponse($response);
            $e->stopPropagation();

            return;
        }

        // Handle NotAuthenticatedException
        var_dump("not authenticated");
        $viewModel = new ViewModel();
        $viewModel->setTemplate('production' === APP_ENV ? 'error/401' : 'error/debug/401');
        $e->getViewModel()->addChild($viewModel);
        $response->setStatusCode(HttpResponse::STATUS_CODE_401);
        $e->setResponse($response);

        $e->stopPropagation();
    }
}
