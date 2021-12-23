<?php

namespace User;

use Doctrine\Laminas\Hydrator\DoctrineObject;
use Laminas\Authentication\AuthenticationService as LaminasAuthenticationService;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\MvcEvent;
use Interop\Container\ContainerInterface;
use User\Authentication\{
    Adapter\ApiMapper,
    Adapter\Mapper,
    Adapter\PinMapper,
    ApiAuthenticationService,
    AuthenticationService,
    Service\LoginAttempt as LoginAttemptService,
};
use User\Authorization\AclServiceFactory;
use User\Mapper\{
    User as UserMapper,
    Factory\ApiAppFactory as ApiAppMapperFactory,
    ApiUser as ApiUserMapper,
    LoginAttempt as LoginAttemptMapper,
    NewUser as NewUserMapper,
    ApiApp as ApiAppMapper,
};
use User\Form\{
    Activate as ActivateForm,
    ApiToken as ApiTokenForm,
    Login as LoginForm,
    Password as PasswordForm,
    Register as RegisterForm,
};
use User\Permissions\NotAllowedException;
use User\Service\{
    ApiApp as ApiAppService,
    ApiUser as ApiUserService,
    Factory\ApiAppFactory as ApiAppServiceFactory,
    Email as EmailService,
    User as UserService,
};

class Module
{
    /**
     * Bootstrap.
     *
     * @param MvcEvent $e
     */
    public function onBootstrap(MvcEvent $e): void
    {
        $em = $e->getApplication()->getEventManager();

        // check if the user has a valid API token
        $request = $e->getRequest();

        if (($request instanceof HttpRequest) && $request->getHeaders()->has('X-Auth-Token')) {
            // check if this is a valid token
            $token = $request->getHeader('X-Auth-Token')
                ->getFieldValue();

            $container = $e->getApplication()->getServiceManager();
            /** @var ApiAuthenticationService $service */
            $service = $container->get('user_apiauth_service');
            $service->authenticate($token);
        }

        // this event listener will turn the request into '403 Forbidden' when
        // there is a NotAllowedException
        $em->attach(
            MvcEvent::EVENT_DISPATCH_ERROR,
            function ($e): void {
                if (
                    'error-exception' == $e->getError()
                    && null != $e->getParam('exception', null)
                    && $e->getParam('exception') instanceof NotAllowedException
                ) {
                    $form = $e->getApplication()->getServiceManager()->get('user_form_login');
                    $e->getResult()->setVariable('form', $form);
                    $e->getResult()->setTemplate((APP_ENV === 'production' ? 'error/403' : 'error/debug/403'));
                    $e->getResponse()->setStatusCode(403);
                }
            },
            -100
        );
    }

    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig(): array
    {
        return [
            'aliases' => [
                LaminasAuthenticationService::class => 'user_auth_service',
            ],
            'factories' => [
                'user_service_user' => function (ContainerInterface $container) {
                    $aclService = $container->get('user_service_acl');
                    $translator = $container->get('translator');
                    $bcrypt = $container->get('user_bcrypt');
                    $authService = $container->get('user_auth_service');
                    $pinMapper = $container->get('user_pin_auth_service');
                    $emailService = $container->get('user_service_email');
                    $userMapper = $container->get('user_mapper_user');
                    $newUserMapper = $container->get('user_mapper_newuser');
                    $memberMapper = $container->get('decision_mapper_member');
                    $registerForm = $container->get('user_form_register');
                    $activateForm = $container->get('user_form_activate');
                    $loginForm = $container->get('user_form_login');
                    $passwordForm = $container->get('user_form_password');

                    return new UserService(
                        $aclService,
                        $translator,
                        $bcrypt,
                        $authService,
                        $pinMapper,
                        $emailService,
                        $userMapper,
                        $newUserMapper,
                        $memberMapper,
                        $registerForm,
                        $activateForm,
                        $loginForm,
                        $passwordForm,
                    );
                },
                'user_service_loginattempt' => function (ContainerInterface $container) {
                    $remoteAddress = $container->get('user_remoteaddress');
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $loginAttemptMapper = $container->get('user_mapper_loginattempt');
                    $userMapper = $container->get('user_mapper_user');
                    $rateLimitConfig = $container->get('config')['login_rate_limits'];

                    return new LoginAttemptService(
                        $remoteAddress,
                        $entityManager,
                        $loginAttemptMapper,
                        $userMapper,
                        $rateLimitConfig,
                    );
                },
                'user_service_apiuser' => function (ContainerInterface $container) {
                    $aclService = $container->get('user_service_acl');
                    $translator = $container->get('translator');
                    $apiUserMapper = $container->get('user_mapper_apiuser');
                    $apiTokenForm = $container->get('user_form_apitoken');

                    return new ApiUserService(
                        $aclService,
                        $translator,
                        $apiUserMapper,
                        $apiTokenForm,
                    );
                },
                'user_service_email' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $renderer = $container->get('ViewRenderer');
                    $transport = $container->get('user_mail_transport');
                    $emailConfig = $container->get('config')['email'];

                    return new EmailService(
                        $translator,
                        $renderer,
                        $transport,
                        $emailConfig,
                    );
                },
                ApiAppMapper::class => ApiAppMapperFactory::class,
                ApiAppService::class => ApiAppServiceFactory::class,
                'user_auth_storage' => function (ContainerInterface $container) {
                    $request = $container->get('Request');
                    $response = $container->get('Response');
                    $config = $container->get('config');

                    return new Authentication\Storage\Session(
                        $request,
                        $response,
                        $config,
                    );
                },
                'user_bcrypt' => function (ContainerInterface $container) {
                    $bcrypt = new Bcrypt();
                    $config = $container->get('config');
                    $bcrypt->setCost($config['bcrypt_cost']);

                    return $bcrypt;
                },

                'user_hydrator' => function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_form_activate' => function (ContainerInterface $container) {
                    return new ActivateForm(
                        $container->get('translator'),
                    );
                },
                'user_form_register' => function (ContainerInterface $container) {
                    return new RegisterForm(
                        $container->get('translator'),
                    );
                },
                'user_form_login' => function (ContainerInterface $container) {
                    return new LoginForm(
                        $container->get('translator'),
                    );
                },
                'user_form_password' => function (ContainerInterface $container) {
                    return new PasswordForm(
                        $container->get('translator'),
                    );
                },
                'user_form_passwordactivate' => function (ContainerInterface $container) {
                    return new ActivateForm(
                        $container->get('translator'),
                    );
                },
                'user_form_apitoken' => function (ContainerInterface $container) {
                    $form = new ApiTokenForm(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('user_hydrator'));

                    return $form;
                },

                'user_mapper_user' => function (ContainerInterface $container) {
                    return new UserMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_mapper_newuser' => function (ContainerInterface $container) {
                    return new NewUserMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_mapper_apiuser' => function (ContainerInterface $container) {
                    return new ApiUserMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_mapper_loginattempt' => function (ContainerInterface $container) {
                    return new LoginAttemptMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },

                'user_mail_transport' => function (ContainerInterface $container) {
                    $config = $container->get('config');
                    $config = $config['email'];
                    $class = '\Laminas\Mail\Transport\\' . $config['transport'];
                    $optionsClass = '\Laminas\Mail\Transport\\' . $config['transport'] . 'Options';
                    $transport = new $class();
                    $transport->setOptions(new $optionsClass($config['options']));

                    return $transport;
                },
                'user_auth_adapter' => function (ContainerInterface $container) {
                    return new Mapper(
                        $container->get('user_bcrypt'),
                        $container->get('user_service_loginattempt'),
                        $container->get('user_mapper_user'),
                    );
                },
                'user_apiauth_adapter' => function (ContainerInterface $container) {
                    return new ApiMapper(
                        $container->get('user_mapper_apiuser'),
                    );
                },
                'user_pin_auth_adapter' => function (ContainerInterface $container) {
                    return new PinMapper(
                        $container->get('user_service_loginattempt'),
                        $container->get('user_mapper_user'),
                    );
                },
                'user_auth_service' => function (ContainerInterface $container) {
                    return new AuthenticationService(
                        $container->get('user_auth_storage'),
                        $container->get('user_auth_adapter'),
                    );
                },
                'user_apiauth_service' => function (ContainerInterface $container) {
                    return new ApiAuthenticationService(
                        $container->get('user_apiauth_adapter'),
                    );
                },
                'user_pin_auth_service' => function (ContainerInterface $container) {
                    return new AuthenticationService(
                        $container->get('user_auth_storage'),
                        $container->get('user_pin_auth_adapter'),
                    );
                },
                'user_remoteaddress' => function (ContainerInterface $container) {
                    $remote = new RemoteAddress();
                    $isProxied = $container->get('config')['proxy']['enabled'];
                    $trustedProxies = $container->get('config')['proxy']['ip_addresses'];
                    $proxyHeader = $container->get('config')['proxy']['header'];

                    $remote->setUseProxy($isProxied)
                        ->setTrustedProxies($trustedProxies)
                        ->setProxyHeader($proxyHeader);

                    return $remote->getIpAddress();
                },
                'user_service_acl' => AclServiceFactory::class,
            ],
        ];
    }
}
