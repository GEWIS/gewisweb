<?php

declare(strict_types=1);

namespace User\Listener;

use Application\Model\Enums\AuthTypes;
use InvalidArgumentException;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
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

use function strlen;
use function substr;

final readonly class Authentication
{
    /**
     * @psalm-param UserAuthenticationService<UserSession, UserAdapter> $userAuthService
     * @psalm-param CompanyUserAuthenticationService<CompanyUserSession, CompanyUserAdapter> $companyUserAuthService
     */
    public function __construct(
        private UserAuthenticationService $userAuthService,
        private CompanyUserAuthenticationService $companyUserAuthService,
        private ApiAuthenticationService $apiUserAuthService,
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
            AuthTypes::Member, AuthTypes::CompanyUser => $this->userAuth($e, $match->getParam('auth_type')),
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
     * @psalm-param AuthTypes::Member|AuthTypes::CompanyUser $authType
     */
    private function userAuth(
        MvcEvent $e,
        AuthTypes $authType,
    ): ?HttpResponse {
        $authService = match ($authType) {
            AuthTypes::Member => $this->userAuthService,
            AuthTypes::CompanyUser => $this->companyUserAuthService,
            default => throw new InvalidArgumentException('Unknown auth type: ' . $authType),
        };

        if ($authService->hasIdentity()) {
            // User is logged in, just continue normally.
            return null;
        }

        $response = $e->getResponse() ?: new HttpResponse();
        if (200 === $response->getStatusCode()) {
            $response->setStatusCode(HttpResponse::STATUS_CODE_401);
        }

        // Switch auth services to determine whether we are logged in as the other type.
        [$realAuthType, $authService] = match ($authType) {
            AuthTypes::Member => [AuthTypes::CompanyUser, $this->companyUserAuthService],
            AuthTypes::CompanyUser => [AuthTypes::Member, $this->userAuthService],
            default => throw new InvalidArgumentException('Unknown auth type: ' . $authType),
        };

        if ($authService->hasIdentity()) {
            $response->setStatusCode(HttpResponse::STATUS_CODE_403);

            $model = new ViewModel([
                'expectedAuthType' => $authType,
                'authType' => $realAuthType,
            ]);
            $model->setTemplate('production' === APP_ENV ? 'error/403' : 'error/debug/403');
        } else {
            $model = new ViewModel([
                'authType' => $authType,
            ]);
            $model->setTemplate('error/401');
        }

        $e->getViewModel()->addChild($model);
        $e->setResult($model);
        $e->setResponse($response);
        $e->stopPropagation();

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
        $response = $e->getResponse() ?: new HttpResponse();
        $response->getHeaders()->addHeaderLine('WWW-Authenticate', 'Bearer realm="/api"');
        $response->setStatusCode(HttpResponse::STATUS_CODE_401);

        $e->setResponse($response);
        $e->stopPropagation();

        return $response;
    }
}
