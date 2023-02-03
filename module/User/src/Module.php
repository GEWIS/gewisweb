<?php

namespace User;

use Doctrine\Laminas\Hydrator\DoctrineObject;
use Laminas\Authentication\AuthenticationService as LaminasAuthenticationService;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Mvc\MvcEvent;
use Psr\Container\ContainerInterface;
use User\Authentication\{
    Adapter\ApiUserAdapter,
    Adapter\CompanyUserAdapter,
    Adapter\UserAdapter,
    ApiAuthenticationService,
    AuthenticationService,
    Service\LoginAttempt as LoginAttemptService,
    Storage\CompanyUserSession,
    Storage\UserSession,
};
use User\Authorization\AclServiceFactory;
use User\Form\{
    Activate as ActivateForm,
    ApiAppAuthorisation as ApiAppAuthorisationForm,
    ApiToken as ApiTokenForm,
    CompanyUserLogin as CompanyUserLoginForm,
    CompanyUserReset as CompanyUserResetForm,
    Password as PasswordForm,
    Register as RegisterForm,
    Reset as ResetForm,
    UserLogin as UserLoginForm,
};
use User\Mapper\{
    ApiApp as ApiAppMapper,
    ApiAppAuthentication as ApiAppAuthenticationMapper,
    ApiUser as ApiUserMapper,
    CompanyUser as CompanyUserMapper,
    Factory\ApiAppFactory as ApiAppMapperFactory,
    LoginAttempt as LoginAttemptMapper,
    NewCompanyUser as NewCompanyUserMapper,
    NewUser as NewUserMapper,
    User as UserMapper,
};
use User\Permissions\NotAllowedException;
use User\Service\{
    ApiApp as ApiAppService,
    ApiUser as ApiUserService,
    Email as EmailService,
    Factory\ApiAppFactory as ApiAppServiceFactory,
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
            $service = $container->get('user_auth_apiUser_service');
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
                LaminasAuthenticationService::class => 'user_auth_user_service',
            ],
            'factories' => [
                'user_service_user' => function (ContainerInterface $container) {
                    $aclService = $container->get('user_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $bcrypt = $container->get('user_bcrypt');
                    $userAuthService = $container->get('user_auth_user_service');
                    $companyUserAuthService = $container->get('user_auth_companyUser_service');
                    $emailService = $container->get('user_service_email');
                    $companyUserMapper = $container->get('user_mapper_companyUser');
                    $userMapper = $container->get('user_mapper_user');
                    $newUserMapper = $container->get('user_mapper_newUser');
                    $newCompanyUserMapper = $container->get('user_mapper_newCompanyUser');
                    $companyMapper = $container->get('company_mapper_company');
                    $memberMapper = $container->get('decision_mapper_member');
                    $registerForm = $container->get('user_form_register');
                    $activateFormCompanyUser = $container->get('user_form_activate_companyUser');
                    $activateFormUser = $container->get('user_form_activate_user');
                    $loginForm = $container->get('user_form_userLogin');
                    $companyUserLoginForm = $container->get('user_form_companyUserLogin');
                    $companyUserResetForm = $container->get('user_form_companyUserReset');
                    $passwordFormCompanyUser = $container->get('user_form_password_companyUser');
                    $passwordFormUser = $container->get('user_form_password_user');
                    $resetForm = $container->get('user_form_reset');
                    $pwnedPasswordsHost = $container->get('config')['passwords']['pwned_passwords_host'];

                    return new UserService(
                        $aclService,
                        $translator,
                        $bcrypt,
                        $userAuthService,
                        $companyUserAuthService,
                        $emailService,
                        $companyUserMapper,
                        $userMapper,
                        $newUserMapper,
                        $newCompanyUserMapper,
                        $companyMapper,
                        $memberMapper,
                        $registerForm,
                        $activateFormCompanyUser,
                        $activateFormUser,
                        $loginForm,
                        $companyUserLoginForm,
                        $companyUserResetForm,
                        $passwordFormCompanyUser,
                        $passwordFormUser,
                        $resetForm,
                        $pwnedPasswordsHost,
                    );
                },
                'user_service_loginattempt' => function (ContainerInterface $container) {
                    $remoteAddress = $container->get('user_remoteaddress');
                    $loginAttemptMapper = $container->get('user_mapper_loginAttempt');
                    $companyUserMapper = $container->get('user_mapper_companyUser');
                    $userMapper = $container->get('user_mapper_user');
                    $rateLimitConfig = $container->get('config')['login_rate_limits'];

                    return new LoginAttemptService(
                        $remoteAddress,
                        $loginAttemptMapper,
                        $companyUserMapper,
                        $userMapper,
                        $rateLimitConfig,
                    );
                },
                'user_service_apiuser' => function (ContainerInterface $container) {
                    $aclService = $container->get('user_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $apiUserMapper = $container->get('user_mapper_apiUser');
                    $apiTokenForm = $container->get('user_form_apitoken');

                    return new ApiUserService(
                        $aclService,
                        $translator,
                        $apiUserMapper,
                        $apiTokenForm,
                    );
                },
                'user_service_email' => function (ContainerInterface $container) {
                    $translator = $container->get(MvcTranslator::class);
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
                'user_auth_user_storage' => function (ContainerInterface $container) {
                    $request = $container->get('Request');
                    $response = $container->get('Response');
                    $config = $container->get('config');

                    return new UserSession(
                        $request,
                        $response,
                        $config,
                    );
                },
                'user_auth_companyUser_storage' => function () {
                    return new CompanyUserSession();
                },
                'user_bcrypt' => function (ContainerInterface $container) {
                    $bcrypt = new Bcrypt();
                    $config = $container->get('config')['passwords'];
                    $bcrypt->setCost($config['bcrypt_cost']);

                    return $bcrypt;
                },

                'user_hydrator' => function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_form_activate_companyUser' => function (ContainerInterface $container) {
                    return new ActivateForm(
                        $container->get(MvcTranslator::class),
                        $container->get('config')['passwords']['min_length_companyUser'],
                    );
                },
                'user_form_activate_user' => function (ContainerInterface $container) {
                    return new ActivateForm(
                        $container->get(MvcTranslator::class),
                        $container->get('config')['passwords']['min_length_user'],
                    );
                },
                'user_form_register' => function (ContainerInterface $container) {
                    return new RegisterForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'user_form_companyUserLogin' => function (ContainerInterface $container) {
                    return new CompanyUserLoginForm(
                        $container->get(MvcTranslator::class),
                        $container->get('config')['passwords']['min_length_companyUser'],
                    );
                },
                'user_form_userLogin' => function (ContainerInterface $container) {
                    return new UserLoginForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'user_form_companyUserReset' => function (ContainerInterface $container) {
                    return new CompanyUserResetForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'user_form_password_companyUser' => function (ContainerInterface $container) {
                    return new PasswordForm(
                        $container->get(MvcTranslator::class),
                        $container->get('config')['passwords']['min_length_companyUser'],
                    );
                },
                'user_form_password_user' => function (ContainerInterface $container) {
                    return new PasswordForm(
                        $container->get(MvcTranslator::class),
                        $container->get('config')['passwords']['min_length_user'],
                    );
                },
                'user_form_reset' => function (ContainerInterface $container) {
                    return new ResetForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'user_form_apitoken' => function (ContainerInterface $container) {
                    $form = new ApiTokenForm(
                        $container->get(MvcTranslator::class)
                    );
                    $form->setHydrator($container->get('user_hydrator'));

                    return $form;
                },
                'user_form_apiappauthorisation_initial' => function (ContainerInterface $container) {
                    return new ApiAppAuthorisationForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'user_form_apiappauthorisation_reminder' => function (ContainerInterface $container) {
                    return new ApiAppAuthorisationForm(
                        $container->get(MvcTranslator::class),
                        'reminder',
                    );
                },

                'user_mapper_apiappauthentication' => function (ContainerInterface $container) {
                    return new ApiAppAuthenticationMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_mapper_user' => function (ContainerInterface $container) {
                    return new UserMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_mapper_companyUser' => function (ContainerInterface $container) {
                    return new CompanyUserMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_mapper_newUser' => function (ContainerInterface $container) {
                    return new NewUserMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_mapper_newCompanyUser' => function (ContainerInterface $container) {
                    return new NewCompanyUserMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_mapper_apiUser' => function (ContainerInterface $container) {
                    return new ApiUserMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_mapper_loginAttempt' => function (ContainerInterface $container) {
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
                'user_auth_user_adapter' => function (ContainerInterface $container) {
                    return new UserAdapter(
                        $container->get('user_bcrypt'),
                        $container->get('user_service_loginattempt'),
                        $container->get('user_mapper_user'),
                    );
                },
                'user_auth_companyUser_adapter' => function (ContainerInterface $container) {
                    return new CompanyUserAdapter(
                        $container->get('user_bcrypt'),
                        $container->get('user_service_loginattempt'),
                        $container->get('user_mapper_companyUser'),
                    );
                },
                'user_auth_apiUser_adapter' => function (ContainerInterface $container) {
                    return new ApiUserAdapter(
                        $container->get('user_mapper_apiUser'),
                    );
                },
                'user_auth_user_service' => function (ContainerInterface $container) {
                    return new AuthenticationService(
                        $container->get('user_auth_user_storage'),
                        $container->get('user_auth_user_adapter'),
                    );
                },
                'user_auth_companyUser_service' => function (ContainerInterface $container) {
                    return new AuthenticationService(
                        $container->get('user_auth_companyUser_storage'),
                        $container->get('user_auth_companyUser_adapter'),
                    );
                },
                'user_auth_apiUser_service' => function (ContainerInterface $container) {
                    return new ApiAuthenticationService(
                        $container->get('user_auth_apiUser_adapter'),
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
