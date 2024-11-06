<?php

declare(strict_types=1);

namespace User\Listener;

use InvalidArgumentException;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface;
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
    // Defining the authentication types
    public const string AUTH_NONE = 'none';
    public const string AUTH_USER = 'user';
    public const string AUTH_COMPANY_USER = 'company_user';
    public const string AUTH_API = 'api';

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

    public function __invoke(MvcEvent $e): ?ResponseInterface
    {
        if (MvcEvent::EVENT_ROUTE !== $e->getName()) {
            throw new InvalidArgumentException(
                'Expected MvcEvent of type ' . MvcEvent::EVENT_ROUTE . ', got ' . $e->getName(),
            );
        }

        $match = $e->getRouteMatch();
        if (null === $match) {
            throw new LogicException('Did not match any route after being routed');
        }

        return match ($match->getParam('auth_type', self::AUTH_NONE)) {
            self::AUTH_USER => $this->userAuth($e, $this->userAuthService),
            self::AUTH_COMPANY_USER => $this->userAuth($e, $this->companyUserAuthService),
            self::AUTH_API => $this->apiAuth($e),
            self::AUTH_NONE => null,
            default => throw new InvalidArgumentException(
                'Authentication type was set to unknown type ' . $match->getParam('auth_type'),
            ),
        };
    }

    /**
     * Handle authentication for (company) users.
     *
     * @psalm-param UserAuthenticationService<UserSession, UserAdapter>|CompanyUserAuthenticationService<CompanyUserSession, CompanyUserAdapter> $authService
     */
    private function userAuth(
        MvcEvent $e,
        AuthenticationServiceInterface $authService,
    ): ?ResponseInterface {
        if ($authService->hasIdentity()) {
            // User is logged in, just continue
            return null;
        }

        /** @var HttpResponse $response */
        $response = $e->getResponse();
//        $e->stopPropagation();

        // If a user of another type is trying to access a route.
        if ($this->isOtherUserAuthenticated($authService)) {
            $viewModel = new ViewModel();
            $viewModel->setTemplate('error/403');

            $e->getViewModel()->addChild($viewModel)
                ->setVariable('exception', 'test');
            $e->setError(Application::ERROR_EXCEPTION)
                ->setParam('exception', new NotAllowedException('Forbidden'));

            return null;
//             $response->setStatusCode(HttpResponse::STATUS_CODE_403);
//             return $response;
        }

//        $viewModel = new ViewModel();
//        $viewModel->setTemplate('production' === APP_ENV ? 'error/403' : 'error/debug/403');
//        $e->getViewModel()->addChild($viewModel);

        // TODO: check if it is possible to pass whether user has to login as user or as company user.
        $viewModel = new ViewModel();
        $viewModel->setTemplate('error/401');

        $e->getViewModel()->addChild($viewModel)
            ->setVariable('exception', 'test');
        $e->setError(Application::ERROR_EXCEPTION)
            ->setParam('exception', new NotAuthenticatedException('Unauthenticated'));

        return null;
//        $response->setStatusCode(HttpResponse::STATUS_CODE_401);
//
//        return $response;
    }

    /**
     * Handle authentication for api tokens
     */
    private function apiAuth(MvcEvent $e): ?ResponseInterface
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
