<?php

declare(strict_types=1);

namespace User;

use Application\Form\Factory\BaseFormFactory;
use Application\Mapper\Factory\BaseMapperFactory;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Laminas\Authentication\AuthenticationService as LaminasAuthenticationService;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Psr\Container\ContainerInterface;
use User\Authentication\Adapter\ApiUserAdapter;
use User\Authentication\Adapter\CompanyUserAdapter;
use User\Authentication\Adapter\Factory\ApiUserAdapterFactory;
use User\Authentication\Adapter\Factory\CompanyUserAdapterFactory;
use User\Authentication\Adapter\Factory\UserAdapterFactory;
use User\Authentication\Adapter\UserAdapter;
use User\Authentication\ApiAuthenticationService;
use User\Authentication\AuthenticationService;
use User\Authentication\Service\Factory\LoginAttemptFactory as LoginAttemptServiceFactory;
use User\Authentication\Service\LoginAttempt as LoginAttemptService;
use User\Authentication\Storage\CompanyUserSession as CompanyUserSessionStorage;
use User\Authentication\Storage\Factory\UserSessionFactory as UserSessionStorageFactory;
use User\Authentication\Storage\UserSession as UserSessionStorage;
use User\Authorization\AclServiceFactory;
use User\Command\DeleteOldLoginAttempts as DeleteOldLoginAttemptsCommands;
use User\Command\Factory\DeleteOldLoginAttemptsFactory as DeleteOldLoginAttemptsCommandFactory;
use User\Form\Activate as ActivateForm;
use User\Form\ApiAppAuthorisation as ApiAppAuthorisationForm;
use User\Form\ApiToken as ApiTokenForm;
use User\Form\CompanyUserLogin as CompanyUserLoginForm;
use User\Form\CompanyUserReset as CompanyUserResetForm;
use User\Form\Factory\ApiTokenFactory as ApiTokenFormFactory;
use User\Form\Factory\CompanyUserLoginFactory as CompanyUserLoginFormFactory;
use User\Form\Factory\UserLoginFactory as UserLoginFormFactory;
use User\Form\Password as PasswordForm;
use User\Form\Register as RegisterForm;
use User\Form\UserLogin as UserLoginForm;
use User\Form\UserReset as UserResetForm;
use User\Mapper\ApiApp as ApiAppMapper;
use User\Mapper\ApiAppAuthentication as ApiAppAuthenticationMapper;
use User\Mapper\ApiUser as ApiUserMapper;
use User\Mapper\CompanyUser as CompanyUserMapper;
use User\Mapper\LoginAttempt as LoginAttemptMapper;
use User\Mapper\NewCompanyUser as NewCompanyUserMapper;
use User\Mapper\NewUser as NewUserMapper;
use User\Mapper\User as UserMapper;
use User\Permissions\NotAllowedException;
use User\Service\AclService;
use User\Service\ApiApp as ApiAppService;
use User\Service\ApiUser as ApiUserService;
use User\Service\Email as EmailService;
use User\Service\Factory\ApiAppFactory as ApiAppServiceFactory;
use User\Service\Factory\ApiUserFactory as ApiUserServiceFactory;
use User\Service\Factory\EmailFactory as EmailServiceFactory;
use User\Service\Factory\PwnedPasswordsFactory as PwnedPasswordsServiceFactory;
use User\Service\Factory\UserFactory as UserServiceFactory;
use User\Service\PwnedPasswords as PwnedPasswordsService;
use User\Service\User as UserService;

use function array_map;
use function array_merge;
use function explode;
use function ip2long;
use function range;
use function str_contains;

class Module
{
    /**
     * Bootstrap.
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
            $service = $container->get(ApiAuthenticationService::class);
            $service->authenticate($token);
        }

        // this event listener will turn the request into '403 Forbidden' when
        // there is a NotAllowedException
        $em->attach(
            MvcEvent::EVENT_DISPATCH_ERROR,
            static function ($e): void {
                if (
                    'error-exception' !== $e->getError()
                    || null === $e->getParam('exception', null)
                    || !($e->getParam('exception') instanceof NotAllowedException)
                ) {
                    return;
                }

                $e->getResult()->setTemplate(('production' === APP_ENV ? 'error/403' : 'error/debug/403'));
                $e->getResponse()->setStatusCode(403);
            },
            -100,
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
                // Services
                AclService::class => AclServiceFactory::class,
                UserService::class => UserServiceFactory::class,
                LoginAttemptService::class => LoginAttemptServiceFactory::class,
                ApiUserService::class => ApiUserServiceFactory::class,
                EmailService::class => EmailServiceFactory::class,
                ApiAppMapper::class => BaseMapperFactory::class,
                ApiAppService::class => ApiAppServiceFactory::class,
                PwnedPasswordsService::class => PwnedPasswordsServiceFactory::class,
                // AUTH
                UserAdapter::class => UserAdapterFactory::class,
                CompanyUserAdapter::class => CompanyUserAdapterFactory::class,
                ApiUserAdapter::class => ApiUserAdapterFactory::class,
                UserSessionStorage::class => UserSessionStorageFactory::class,
                CompanyUserSessionStorage::class => InvokableFactory::class,
                'user_auth_user_service' => static function (ContainerInterface $container) {
                    return new AuthenticationService(
                        $container->get(UserSessionStorage::class),
                        $container->get(UserAdapter::class),
                    );
                },
                'user_auth_companyUser_service' => static function (ContainerInterface $container) {
                    return new AuthenticationService(
                        $container->get(CompanyUserSessionStorage::class),
                        $container->get(CompanyUserAdapter::class),
                    );
                },
                ApiAuthenticationService::class => static function (ContainerInterface $container) {
                    return new ApiAuthenticationService(
                        $container->get(ApiUserAdapter::class),
                    );
                },
                'user_bcrypt' => static function (ContainerInterface $container) {
                    $bcrypt = new Bcrypt();
                    $config = $container->get('config')['passwords'];
                    $bcrypt->setCost($config['bcrypt_cost']);

                    return $bcrypt;
                },
                // END AUTH
                // Mappers
                ApiAppAuthenticationMapper::class => BaseMapperFactory::class,
                ApiUserMapper::class => BaseMapperFactory::class,
                CompanyUserMapper::class => BaseMapperFactory::class,
                LoginAttemptMapper::class => BaseMapperFactory::class,
                NewCompanyUserMapper::class => BaseMapperFactory::class,
                NewUserMapper::class => BaseMapperFactory::class,
                UserMapper::class => BaseMapperFactory::class,
                // Forms
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
                ApiAppAuthorisationForm::class => BaseFormFactory::class,
                'user_form_apiappauthorisation_reminder' => static function (ContainerInterface $container) {
                    return new ApiAppAuthorisationForm(
                        $container->get(MvcTranslator::class),
                        'reminder',
                    );
                },
                ApiTokenForm::class => ApiTokenFormFactory::class,
                CompanyUserLoginForm::class => CompanyUserLoginFormFactory::class,
                CompanyUserResetForm::class => BaseFormFactory::class,
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
                RegisterForm::class => BaseFormFactory::class,
                UserLoginForm::class => UserLoginFormFactory::class,
                UserResetForm::class => BaseFormFactory::class,
                'user_hydrator' => static function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                // Commands
                DeleteOldLoginAttemptsCommands::class => DeleteOldLoginAttemptsCommandFactory::class,
                // Misc
                'user_mail_transport' => static function (ContainerInterface $container) {
                    $config = $container->get('config');
                    $config = $config['email'];
                    $class = '\Laminas\Mail\Transport\\' . $config['transport'];
                    $optionsClass = '\Laminas\Mail\Transport\\' . $config['transport'] . 'Options';
                    $transport = new $class();
                    $transport->setOptions(new $optionsClass($config['options']));

                    return $transport;
                },
                'user_remoteaddress' => static function (ContainerInterface $container) {
                    $remote = new RemoteAddress();
                    $isProxied = $container->get('config')['proxy']['enabled'];
                    /** @psalm-suppress NamedArgumentNotAllowed */
                    $trustedProxies = array_merge(
                        ...array_map(
                            static function (string $ip) {
                                if (str_contains($ip, '/')) {
                                    [$subnet, $bits] = explode('/', $ip);
                                    $bits = (int) $bits;

                                    // Ensure that the subnet is valid.
                                    if (
                                        0 > $bits
                                        || 32 < $bits
                                        || false === ip2long($subnet)
                                    ) {
                                        return [];
                                    }

                                    // Precompute the netmask and re-align the range.
                                    $netmask = -1 << 32 - $bits;
                                    $start = ip2long($subnet) & $netmask;
                                    $end = ip2long($subnet) | ~$netmask;

                                    return array_map('long2ip', range($start, $end));
                                }

                                return [$ip];
                            },
                            $container->get('config')['proxy']['ip_addresses'],
                        ),
                    );
                    $proxyHeader = $container->get('config')['proxy']['header'];

                    $remote->setUseProxy($isProxied)
                        ->setTrustedProxies($trustedProxies)
                        ->setProxyHeader($proxyHeader);

                    return $remote->getIpAddress();
                },
            ],
        ];
    }
}
