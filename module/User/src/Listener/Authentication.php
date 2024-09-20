<?php

declare(strict_types=1);

namespace User\Listener;

use Application\Model\Enums\AuthTypes;
use Cassandra\Exception\UnauthorizedException;
use InvalidArgumentException;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;
use LogicException;
use User\Authentication\Adapter\CompanyUserAdapter;
use User\Authentication\Adapter\UserAdapter;
use User\Authentication\ApiAuthenticationService;
use User\Authentication\AuthenticationService as CompanyUserAuthenticationService;
use User\Authentication\AuthenticationService as UserAuthenticationService;
use User\Authentication\Storage\CompanyUserSession;
use User\Authentication\Storage\UserSession;
use User\Permissions\NotAllowedException;
use User\Permissions\NotAuthenticatedException;

use function strlen;
use function substr;

final class Authentication
{
    public const string AUTH_EXCEPTION = 'auth-exception';

    /**
     * @psalm-param UserAuthenticationService<UserSession, UserAdapter> $userAuthService
     * @psalm-param CompanyUserAuthenticationService<CompanyUserSession, CompanyUserAdapter> $companyUserAuthService
     */
    public function __construct(
        private readonly UserAuthenticationService $userAuthService,
        private readonly CompanyUserAuthenticationService $companyUserAuthService,
        private readonly ApiAuthenticationService $apiUserAuthService,
    ) {
    }

    public function __invoke(MvcEvent $e): ?HttpResponse
    {
        if (MvcEvent::EVENT_ROUTE !== $e->getName()) {
            throw new InvalidArgumentException(
                'Expected MvcEvent of type ' . MvcEvent::EVENT_ROUTE . ', got ' . $e->getName(),
            );
        }

        $request  = $e->getRequest();
        $response = $e->getResponse();
        if (!($request instanceof HttpRequest && $response instanceof HttpResponse)) {
            return null;
        }

        $match = $e->getRouteMatch();
        if (null === $match) {
            throw new LogicException('Did not match any route after being routed');
        }

        return match ($match->getParam('auth_type', AuthTypes::None)) {
            AuthTypes::User, AuthTypes::CompanyUser => $this->userAuth($e, $match->getParam('auth_type')),
            AuthTypes::Api => $this->apiAuth($e),
            AuthTypes::None => null,
            default => throw new InvalidArgumentException(
                'Authentication type was set to unknown type ' . $match->getParam('auth_type'),
            ),
        };
    }

    /**
     * Handle authentication for (company) users.
     *
     * @psalm-param AuthTypes::User|AuthType::CompanyUser $authType
     */
    private function userAuth(
        MvcEvent $e,
        AuthTypes $authType,
    ): ?HttpResponse {
        $authService = match ($authType) {
            AuthTypes::User => $this->userAuthService,
            AuthTypes::CompanyUser => $this->companyUserAuthService,
            default => throw new InvalidArgumentException('Unknown auth type: ' . $authType),
        };

        if ($authService->hasIdentity()) {
            // User is logged in, just continue normally.
            return null;
        }

        // Stop propagating this event. We will build a new event such that we can use our custom event handler to
        // display the error page to the user.
//        $e->stopPropagation();
//
        /** @var HttpResponse $response */
        $response = $e->getResponse();
        $viewModel = new ViewModel();
//        /** @var HttpRequest $request */
//        $request = $e->getRequest();

//        $event = new MvcEvent();
//        $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
//        $event->setError(Application::ERROR_EXCEPTION);
//        $event->setParam('auth_type', $authType);
        // $event->setRequest($request);

        // If a user of another type is trying to access a route.
        if ($this->isOtherUserAuthenticated($authService)) {
//            $event->setParam('exception', new NotAllowedException('Forbidden'));
//            $viewModel->setTemplate('production' === APP_ENV ? 'error/403' : 'error/debug/403');
//
//            $e->getViewModel()->addChild($viewModel)
//                ->setVariable('auth_type', $authType);
//            $e->setError(self::AUTH_EXCEPTION);
//
            $response->setStatusCode(HttpResponse::STATUS_CODE_403);
//
//            $e->getApplication()->getEventManager()->triggerEvent($event);

            return null;
        }

//        $event->setParam('exception', new NotAuthenticatedException('Unauthenticated'));
//
//        $e->getApplication()->getEventManager()->triggerEvent($event);

//        $viewModel->setTemplate('production' === APP_ENV ? 'error/401' : 'error/debug/401');
//
//        $e->getViewModel()->addChild($viewModel)
//            ->setVariable('auth_type', $authType);
//        $e->setError(self::AUTH_EXCEPTION);

        $response->setStatusCode(HttpResponse::STATUS_CODE_401);
//
//        return null;

        return null;
    }

    /**
     * Handle authentication for api tokens
     */
    private function apiAuth(MvcEvent $e): ?HttpResponse
    {
        $request = $e->getRequest();

        // TODO: remove X-Auth-Token authentication after December 31, 2024.
        if ($request->getHeaders()->has('X-Auth-Token')) {
            // check if this is a valid token
            $token = $request->getHeader('X-Auth-Token')->getFieldValue();
            $result = $this->apiUserAuthService->authenticate($token);

            if ($result->isValid()) {
                return null;
            }
        }

        // TODO: make authentication using Bearer the default after December 31, 2024.
        if ($request->getHeaders()->has('Authorization')) {
            // This is an API call, we do this on every request
            $token = $request->getHeader('Authorization')->getFieldValue();
            $result = $this->apiUserAuthService->authenticate(substr($token, strlen('Bearer ')));

            if ($result->isValid()) {
                return null;
            }
        }

        // If authentication failed and if this is an HTTP request, we add authentication headers.
        $response = $e->getResponse();
        if ($response instanceof HttpResponse) {
            $response->getHeaders()->addHeaderLine('WWW-Authenticate', 'Bearer realm="/api"');
            $response->setStatusCode(HttpResponse::STATUS_CODE_401);
        }

        $e->stopPropagation();

        return $response;
    }

    private function isOtherUserAuthenticated(AuthenticationServiceInterface $currentAuthService): bool
    {
        $otherAuthServices = [$this->userAuthService, $this->companyUserAuthService, $this->apiUserAuthService];

        foreach ($otherAuthServices as $authService) {
            if (
                $authService !== $currentAuthService
                && $authService->hasIdentity()
            ) {
                return true;
            }
        }

        return false;
    }
}
