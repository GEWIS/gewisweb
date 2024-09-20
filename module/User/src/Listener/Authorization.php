<?php

declare(strict_types=1);

namespace User\Listener;

use Application\Model\Enums\ApiResponseStatuses;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
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

        $request = $e->getRequest();
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
                $e->getResult()->setTemplate('production' === APP_ENV ? 'error/403' : 'error/debug/403');
                $e->getResponse()->setStatusCode(HttpResponse::STATUS_CODE_403);
            }

//            $e->stopPropagation();

            return;
        }

        if ($e->getParam('exception') instanceof NotAuthenticatedException) {
            $e->getResult()->setTemplate('production' === APP_ENV ? 'error/401' : 'error/debug/401');
            $e->getResponse()->setStatusCode(HttpResponse::STATUS_CODE_401);
        }
    }
}
