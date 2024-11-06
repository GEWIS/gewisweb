<?php

declare(strict_types=1);

namespace User\Listener;

use Application\Model\Enums\ApiResponseStatuses;
use Laminas\Http\Request;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteStackInterface as Router;
use Laminas\View\Model\JsonModel;
use LogicException;

use function str_contains;
use function strrpos;
use function substr;

final class DispatchErrorFormatter
{
    public function __invoke(MvcEvent $e): void
    {
        /**
         * The source code is public so we can give away 404 errors if it is because of no route
         * This does not include 404 responses that are returned by logic, such as when a member does not exist
         **/
        if ('error-router-no-match' === $e->getError()) {
            $this->handleNoMatchedRoute($e);

            return;
        }

        /**
         * If this is not an error-router-no-match error, we must have a matching route
         */
        $match = $e->getRouteMatch();

        // We should always have a match here; if we do not, throw an exception
        // possibly including previous exceptions
        if (null === $match) {
            throw new LogicException(
                message: 'Assumed route would be present; no route present',
                previous: $e->getParam('exception', null),
            );
        }

        // If we do have a match, this implies we have properly authenticated before
        $this->handleMatchedRoute($e, $match);
    }

    private function matchAncestorRoute(
        Request $request,
        Router $router,
    ): ?RouteMatch {
        $request = clone $request;
        $uri = clone $request->getUri();
        $path = $uri->getPath();
        $match = null;

        while (null === $match && str_contains($path, '/')) {
            $path = substr($path, 0, strrpos($path, '/'));

            if ('' === $path) {
                $uri->setPath('/');
            } else {
                $uri->setPath($path);
            }

            $request->setUri($uri);
            $match = $router->match($request);
        }

        return $match;
    }

    private function isApiMatch(RouteMatch $match): bool
    {
        return Authentication::AUTH_API === $match->getParam('auth_type', Authentication::AUTH_NONE);
    }

    private function handleNoMatchedRoute(MvcEvent $e): void
    {
        $router = $e->getRouter();
        $request = $e->getRequest();

        // If this is not an HTTP request, we cannot assume anything about routes
        if (!($request instanceof Request)) {
            return;
        }

        $match = $this->matchAncestorRoute($request, $router);

        // Regular routes are dealt with by default handling
        if (!$this->isApiMatch($match)) {
            return;
        }

        // If this is probably an API route, response should be JSON
        $view = new JsonModel([
            'status' => ApiResponseStatuses::NotFound,
            'error' => [
                'type' => $e->getError(),
                'exception' => $e->getParam('exception')?->getMessage(),
            ],
        ]);

        $e->setViewModel($view);
        $response = $e->getResponse();
        if ($response instanceof HttpResponse) {
            $response->setStatusCode(HttpResponse::STATUS_CODE_404);
        }

        $e->stopPropagation();
    }

    private function handleMatchedRoute(
        MvcEvent $e,
        RouteMatch $match,
    ): void {
        // Regular routes are dealt with by default handling
        if (!$this->isApiMatch($match)) {
            return;
        }

        // If this is probably an API route, response should be JSON
        $view = new JsonModel([
            'status' => ApiResponseStatuses::Error,
            'error' => [
                'type' => $e->getError(),
                'exception' => $e->getParam('exception')?->getMessage(),
            ],
        ]);

        $e->setViewModel($view);
        $response = $e->getResponse();
        if ($response instanceof HttpResponse) {
            $response->setStatusCode(HttpResponse::STATUS_CODE_500);
        }

        $e->stopPropagation();
    }
}
