<?php

declare(strict_types=1);

namespace User;

use Activity\Command\Factory\DeleteOldLoginAttemptsFactory as DeleteOldLoginAttemptsCommandFactory;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Laminas\Authentication\AuthenticationService as LaminasAuthenticationService;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Mvc\MvcEvent;
use Psr\Container\ContainerInterface;
use User\Authentication\Adapter\ApiUserAdapter;
use User\Authentication\Adapter\CompanyUserAdapter;
use User\Authentication\Adapter\UserAdapter;
use User\Authentication\ApiAuthenticationService;
use User\Authentication\AuthenticationService;
use User\Authentication\Service\LoginAttempt as LoginAttemptService;
use User\Authentication\Storage\CompanyUserSession;
use User\Authentication\Storage\UserSession;
use User\Authorization\AclServiceFactory;
use User\Command\DeleteOldLoginAttempts as DeleteOldLoginAttemptsCommands;
use User\Form\Activate as ActivateForm;
use User\Form\ApiAppAuthorisation as ApiAppAuthorisationForm;
use User\Form\ApiToken as ApiTokenForm;
use User\Form\CompanyUserLogin as CompanyUserLoginForm;
use User\Form\CompanyUserReset as CompanyUserResetForm;
use User\Form\Password as PasswordForm;
use User\Form\Register as RegisterForm;
use User\Form\UserLogin as UserLoginForm;
use User\Form\UserReset as ResetForm;
use User\Listener\Authentication;
use User\Listener\Authorization;
use User\Listener\DispatchErrorFormatter;
use User\Mapper\ApiApp as ApiAppMapper;
use User\Mapper\ApiAppAuthentication as ApiAppAuthenticationMapper;
use User\Mapper\ApiUser as ApiUserMapper;
use User\Mapper\CompanyUser as CompanyUserMapper;
use User\Mapper\Factory\ApiAppFactory as ApiAppMapperFactory;
use User\Mapper\LoginAttempt as LoginAttemptMapper;
use User\Mapper\NewCompanyUser as NewCompanyUserMapper;
use User\Mapper\NewUser as NewUserMapper;
use User\Mapper\User as UserMapper;
use User\Service\ApiApp as ApiAppService;
use User\Service\ApiUser as ApiUserService;
use User\Service\Email as EmailService;
use User\Service\Factory\ApiAppFactory as ApiAppServiceFactory;
use User\Service\Factory\PwnedPasswordsFactory as PwnedPasswordsServiceFactory;
use User\Service\PwnedPasswords as PwnedPasswordsService;
use User\Service\User as UserService;

class Module
{
    /**
     * Bootstrap.
     */
    public function onBootstrap(MvcEvent $e): void
    {
        $sm = $e->getApplication()->getServiceManager();
        $em = $e->getApplication()->getEventManager();

        // Establish an identity of the user using the authentication listener.
        /** @var AuthenticationService<UserSession, UserAdapter> $userAuthService */
        $userAuthService = $sm->get('user_auth_user_service');
        /** @var AuthenticationService<CompanyUserSession, CompanyUserAdapter> $companyUserAuthService */
        $companyUserAuthService = $sm->get('user_auth_companyUser_service');
        $apiUserAuthService = $sm->get('user_auth_apiUser_service');
        $em->attach(
            MvcEvent::EVENT_ROUTE,
            new Authentication(
                $userAuthService,
                $companyUserAuthService,
                $apiUserAuthService,
            ),
            -100,
        );

        // Catch authorization exceptions
        // $em->attach(MvcEvent::EVENT_DISPATCH_ERROR, new Authorization(), 10);

        // Format errors in case of dispatch errors after authentication
        // $em->attach(MvcEvent::EVENT_DISPATCH_ERROR, new DispatchErrorFormatter(), 5);

//        $em = $e->getApplication()->getEventManager();
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
                'user_service_user' => static function (ContainerInterface $container) {
                    $aclService = $container->get('user_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $bcrypt = $container->get('user_bcrypt');
                    $userAuthService = $container->get('user_auth_user_service');
                    $companyUserAuthService = $container->get('user_auth_companyUser_service');
                    $emailService = $container->get('user_service_email');
                    $pwnedPasswordsService = $container->get(PwnedPasswordsService::class);
                    $companyUserMapper = $container->get('user_mapper_companyUser');
                    $userMapper = $container->get('user_mapper_user');
                    $newUserMapper = $container->get('user_mapper_newUser');
                    $newCompanyUserMapper = $container->get('user_mapper_newCompanyUser');
                    $memberMapper = $container->get('decision_mapper_member');
                    $registerForm = $container->get('user_form_register');
                    $activateFormCompanyUser = $container->get('user_form_activate_companyUser');
                    $activateFormUser = $container->get('user_form_activate_user');
                    $loginForm = $container->get('user_form_userLogin');
                    $companyUserLoginForm = $container->get('user_form_companyUserLogin');
                    $companyUserResetForm = $container->get('user_form_companyUserReset');
                    $passwordFormCompanyUser = $container->get('user_form_password_companyUser');
                    $passwordFormUser = $container->get('user_form_password_user');
                    $userResetForm = $container->get('user_form_reset');

                    return new UserService(
                        $aclService,
                        $translator,
                        $bcrypt,
                        $userAuthService,
                        $companyUserAuthService,
                        $emailService,
                        $pwnedPasswordsService,
                        $companyUserMapper,
                        $userMapper,
                        $newUserMapper,
                        $newCompanyUserMapper,
                        $memberMapper,
                        $registerForm,
                        $activateFormCompanyUser,
                        $activateFormUser,
                        $loginForm,
                        $companyUserLoginForm,
                        $companyUserResetForm,
                        $passwordFormCompanyUser,
                        $passwordFormUser,
                        $userResetForm,
                    );
                },
                'user_service_loginattempt' => static function (ContainerInterface $container) {
                    $remoteAddress = $container->get('user_remoteaddress');
                    $loginAttemptMapper = $container->get('user_mapper_loginAttempt');
                    $rateLimitConfig = $container->get('config')['login_rate_limits'];

                    return new LoginAttemptService(
                        $remoteAddress,
                        $loginAttemptMapper,
                        $rateLimitConfig,
                    );
                },
                'user_service_apiuser' => static function (ContainerInterface $container) {
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
                'user_service_email' => static function (ContainerInterface $container) {
                    $renderer = $container->get('ViewRenderer');
                    $transport = $container->get('user_mail_transport');
                    $emailConfig = $container->get('config')['email'];

                    return new EmailService(
                        $renderer,
                        $transport,
                        $emailConfig,
                    );
                },
                ApiAppMapper::class => ApiAppMapperFactory::class,
                ApiAppService::class => ApiAppServiceFactory::class,
                PwnedPasswordsService::class => PwnedPasswordsServiceFactory::class,
                'user_auth_user_storage' => static function (ContainerInterface $container) {
                    $request = $container->get('Request');
                    $response = $container->get('Response');
                    $config = $container->get('config');

                    return new UserSession(
                        $request,
                        $response,
                        $config,
                    );
                },
                'user_auth_companyUser_storage' => static function () {
                    return new CompanyUserSession();
                },
                'user_bcrypt' => static function (ContainerInterface $container) {
                    $bcrypt = new Bcrypt();
                    $config = $container->get('config')['passwords'];
                    $bcrypt->setCost($config['bcrypt_cost']);

                    return $bcrypt;
                },

                'user_hydrator' => static function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_form_activate_companyUser' => static function (ContainerInterface $container) {
                    return new ActivateForm(
                        $container->get(MvcTranslator::class),
                        $container->get('config')['passwords']['min_length_companyUser'],
                    );
                },
                'user_form_activate_user' => static function (ContainerInterface $container) {
                    return new ActivateForm(
                        $container->get(MvcTranslator::class),
                        $container->get('config')['passwords']['min_length_user'],
                    );
                },
                'user_form_register' => static function (ContainerInterface $container) {
                    return new RegisterForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'user_form_companyUserLogin' => static function (ContainerInterface $container) {
                    return new CompanyUserLoginForm(
                        $container->get(MvcTranslator::class),
                        $container->get('config')['passwords']['min_length_companyUser'],
                    );
                },
                'user_form_userLogin' => static function (ContainerInterface $container) {
                    return new UserLoginForm(
                        $container->get(MvcTranslator::class),
                        $container->get('config')['passwords']['min_length_user'],
                    );
                },
                'user_form_companyUserReset' => static function (ContainerInterface $container) {
                    return new CompanyUserResetForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'user_form_password_companyUser' => static function (ContainerInterface $container) {
                    return new PasswordForm(
                        $container->get(MvcTranslator::class),
                        $container->get('config')['passwords']['min_length_companyUser'],
                    );
                },
                'user_form_password_user' => static function (ContainerInterface $container) {
                    return new PasswordForm(
                        $container->get(MvcTranslator::class),
                        $container->get('config')['passwords']['min_length_user'],
                    );
                },
                'user_form_reset' => static function (ContainerInterface $container) {
                    return new ResetForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'user_form_apitoken' => static function (ContainerInterface $container) {
                    $form = new ApiTokenForm(
                        $container->get(MvcTranslator::class),
                    );
                    $form->setHydrator($container->get('user_hydrator'));

                    return $form;
                },
                'user_form_apiappauthorisation_initial' => static function (ContainerInterface $container) {
                    return new ApiAppAuthorisationForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'user_form_apiappauthorisation_reminder' => static function (ContainerInterface $container) {
                    return new ApiAppAuthorisationForm(
                        $container->get(MvcTranslator::class),
                        'reminder',
                    );
                },

                'user_mapper_apiappauthentication' => static function (ContainerInterface $container) {
                    return new ApiAppAuthenticationMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_mapper_user' => static function (ContainerInterface $container) {
                    return new UserMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_mapper_companyUser' => static function (ContainerInterface $container) {
                    return new CompanyUserMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_mapper_newUser' => static function (ContainerInterface $container) {
                    return new NewUserMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_mapper_newCompanyUser' => static function (ContainerInterface $container) {
                    return new NewCompanyUserMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_mapper_apiUser' => static function (ContainerInterface $container) {
                    return new ApiUserMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'user_mapper_loginAttempt' => static function (ContainerInterface $container) {
                    return new LoginAttemptMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },

                'user_mail_transport' => static function (ContainerInterface $container) {
                    $config = $container->get('config');
                    $config = $config['email'];
                    $class = '\Laminas\Mail\Transport\\' . $config['transport'];
                    $optionsClass = '\Laminas\Mail\Transport\\' . $config['transport'] . 'Options';
                    $transport = new $class();
                    $transport->setOptions(new $optionsClass($config['options']));

                    return $transport;
                },
                'user_auth_user_adapter' => static function (ContainerInterface $container) {
                    return new UserAdapter(
                        $container->get(MvcTranslator::class),
                        $container->get('user_bcrypt'),
                        $container->get('user_service_loginattempt'),
                        $container->get(PwnedPasswordsService::class),
                        $container->get('user_mapper_user'),
                    );
                },
                'user_auth_companyUser_adapter' => static function (ContainerInterface $container) {
                    return new CompanyUserAdapter(
                        $container->get(MvcTranslator::class),
                        $container->get('user_bcrypt'),
                        $container->get('user_service_loginattempt'),
                        $container->get(PwnedPasswordsService::class),
                        $container->get('user_mapper_companyUser'),
                    );
                },
                'user_auth_apiUser_adapter' => static function (ContainerInterface $container) {
                    return new ApiUserAdapter(
                        $container->get('user_mapper_apiUser'),
                    );
                },
                'user_auth_user_service' => static function (ContainerInterface $container) {
                    return new AuthenticationService(
                        $container->get('user_auth_user_storage'),
                        $container->get('user_auth_user_adapter'),
                    );
                },
                'user_auth_companyUser_service' => static function (ContainerInterface $container) {
                    return new AuthenticationService(
                        $container->get('user_auth_companyUser_storage'),
                        $container->get('user_auth_companyUser_adapter'),
                    );
                },
                'user_auth_apiUser_service' => static function (ContainerInterface $container) {
                    return new ApiAuthenticationService(
                        $container->get('user_auth_apiUser_adapter'),
                    );
                },
                'user_remoteaddress' => static function (ContainerInterface $container) {
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
                DeleteOldLoginAttemptsCommands::class => DeleteOldLoginAttemptsCommandFactory::class,
            ],
        ];
    }
}
