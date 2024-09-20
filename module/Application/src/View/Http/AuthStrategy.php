<?php

declare(strict_types=1);

namespace Application\View\Http;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\Response;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;
use User\Permissions\NotAuthenticatedException;

class AuthStrategy extends AbstractListenerAggregate
{
    public function __construct(
        private readonly string $unauthenticatedTemplate = 'error',
        private readonly string $unauthorizedTemplate = 'error'
    ) {
    }

    /**
     * @param int $priority
     */
    public function attach(
        EventManagerInterface $events,
        $priority = 1
    ): void {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_DISPATCH_ERROR,
            [$this, 'prepareAuthViewModel'],
            10
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_RENDER_ERROR,
            [$this, 'prepareAuthViewModel'],
            10
        );
    }

    public function prepareAuthViewModel(MvcEvent $event): void
    {
        // We only handle errors here, if there is no error we do not need to do anything.
        $error = $event->getError();
        if (empty($error)) {
            return;
        }

        // Do nothing if the result is a response object (the assumption here is that we have already handled this).
        $result = $event->getResult();
        if ($result instanceof Response) {
            return;
        }

        // Check the exception type.
        $exception = $event->getParam('exception');
        if ($exception instanceof NotAuthenticatedException) {
            $template = $this->unauthenticatedTemplate;
            $statusCode = HttpResponse::STATUS_CODE_401;
        } elseif ($exception instanceof NotAllowedException) {
            $template = $this->unauthorizedTemplate;
            $statusCode = HttpResponse::STATUS_CODE_403;
        } else {
            // Not a relevant exception (will be handled by other listeners).
            return;
        }

        $model = new ViewModel([]);
        $model->setTemplate($template);
        $event->setResult($model);

        $response = $event->getResponse() ?: new HttpResponse();
        if ($response->getStatusCode() === 200) {
            $response->setStatusCode($statusCode);
        }

        $event->setResponse($response);
    }
}
