<?php

namespace User;

use Doctrine\Laminas\Hydrator\DoctrineObject;
use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\MvcEvent;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\GenericResource as Resource;
use Laminas\Permissions\Acl\Role\GenericRole as Role;
use Laminas\ServiceManager\ServiceLocatorInterface;
use User\Authentication\Adapter\Mapper;
use User\Authentication\Adapter\PinMapper;
use User\Form\Activate;
use User\Form\ApiToken;
use User\Form\Login;
use User\Form\Password;
use User\Form\Register;
use User\Mapper\ApiUser;
use User\Mapper\LoginAttempt;
use User\Mapper\NewUser;
use User\Mapper\Session;
use User\Model\User;
use User\Permissions\Assertion\IsBoardMember;
use User\Permissions\NotAllowedException;
use User\Service\ApiApp;
use User\Service\Email;
use User\Service\Factory\ApiAppFactory;

class Module
{
    /**
     * Bootstrap.
     *
     * @var MvcEvent
     */
    public function onBootstrap(MvcEvent $e)
    {
        $em = $e->getApplication()->getEventManager();

        // check if the user has a valid API token
        $request = $e->getRequest();

        if (($request instanceof HttpRequest) && $request->getHeaders()->has('X-Auth-Token')) {
            // check if this is a valid token
            $token = $request->getHeader('X-Auth-Token')
                ->getFieldValue();

            $sm = $e->getApplication()->getServiceManager();
            $service = $sm->get('user_service_apiuser');
            $service->verifyToken($token);
        }

        // this event listener will turn the request into '403 Forbidden' when
        // there is a NotAllowedException
        $em->attach(
            MvcEvent::EVENT_DISPATCH_ERROR,
            function ($e) {
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
    public function getServiceConfig()
    {
        return [
            'aliases' => [
                'Laminas\Authentication\AuthenticationService' => 'user_auth_service',
            ],

            'factories' => [
                'user_service_user' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $bcrypt = $sm->get('user_bcrypt');
                    $authService = $sm->get('user_auth_service');
                    $pinMapper = $sm->get('user_pin_auth_service');
                    $authStorage = $sm->get('user_auth_storage');
                    $emailService = $sm->get('user_service_email');
                    $acl = $sm->get('acl');
                    $userMapper = $sm->get('user_mapper_user');
                    $newUserMapper = $sm->get('user_mapper_newuser');
                    $memberMapper = $sm->get('decision_mapper_member');
                    $registerForm = $sm->get('user_form_register');
                    $activateForm = $sm->get('user_form_activate');
                    $loginForm = $sm->get('user_form_login');
                    $passwordForm = $sm->get('user_form_password');

                    return new Service\User(
                        $translator,
                        $userRole,
                        $bcrypt,
                        $authService,
                        $pinMapper,
                        $authStorage,
                        $emailService,
                        $acl,
                        $userMapper,
                        $newUserMapper,
                        $memberMapper,
                        $registerForm,
                        $activateForm,
                        $loginForm,
                        $passwordForm
                    );
                },
                'user_service_loginattempt' => function (ServiceLocatorInterface $sm) {
                    $remoteAddress = $sm->get('user_remoteaddress');
                    $entityManager = $sm->get('doctrine.entitymanager.orm_default');
                    $loginAttemptMapper = $sm->get('user_mapper_loginattempt');
                    $userMapper = $sm->get('user_mapper_user');
                    $rateLimitConfig = $sm->get('config')['login_rate_limits'];

                    return new Service\LoginAttempt(
                        $remoteAddress,
                        $entityManager,
                        $loginAttemptMapper,
                        $userMapper,
                        $rateLimitConfig
                    );
                },
                'user_service_apiuser' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('acl');
                    $apiUserMapper = $sm->get('user_mapper_apiuser');
                    $apiTokenForm = $sm->get('user_form_apitoken');

                    return new Service\ApiUser($translator, $userRole, $acl, $apiUserMapper, $apiTokenForm);
                },
                'user_service_email' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');
                    $renderer = $sm->get('ViewRenderer');
                    $transport = $sm->get('user_mail_transport');
                    $emailConfig = $sm->get('config')['email'];

                    return new Email($translator, $renderer, $transport, $emailConfig);
                },
                ApiApp::class => ApiAppFactory::class,
                'user_auth_storage' => function (ServiceLocatorInterface $sm) {
                    $request = $sm->get('Request');
                    $response = $sm->get('Response');
                    $config = $sm->get('config');

                    return new Authentication\Storage\Session(
                        $request,
                        $response,
                        $config
                    );
                },
                'user_bcrypt' => function (ServiceLocatorInterface $sm) {
                    $bcrypt = new Bcrypt();
                    $config = $sm->get('config');
                    $bcrypt->setCost($config['bcrypt_cost']);

                    return $bcrypt;
                },

                'user_hydrator' => function (ServiceLocatorInterface $sm) {
                    return new DoctrineObject(
                        $sm->get('doctrine.entitymanager.orm_default')
                    );
                },
                'user_form_activate' => function (ServiceLocatorInterface $sm) {
                    return new Activate(
                        $sm->get('translator')
                    );
                },
                'user_form_register' => function (ServiceLocatorInterface $sm) {
                    return new Register(
                        $sm->get('translator')
                    );
                },
                'user_form_login' => function (ServiceLocatorInterface $sm) {
                    return new Login(
                        $sm->get('translator')
                    );
                },
                'user_form_password' => function (ServiceLocatorInterface $sm) {
                    return new Password(
                        $sm->get('translator')
                    );
                },
                'user_form_passwordactivate' => function (ServiceLocatorInterface $sm) {
                    return new Activate(
                        $sm->get('translator')
                    );
                },
                'user_form_apitoken' => function (ServiceLocatorInterface $sm) {
                    $form = new ApiToken(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('user_hydrator'));

                    return $form;
                },

                'user_mapper_user' => function (ServiceLocatorInterface $sm) {
                    return new \User\Mapper\User(
                        $sm->get('doctrine.entitymanager.orm_default')
                    );
                },
                'user_mapper_newuser' => function (ServiceLocatorInterface $sm) {
                    return new NewUser(
                        $sm->get('doctrine.entitymanager.orm_default')
                    );
                },
                'user_mapper_apiuser' => function (ServiceLocatorInterface $sm) {
                    return new ApiUser(
                        $sm->get('doctrine.entitymanager.orm_default')
                    );
                },
                'user_mapper_session' => function (ServiceLocatorInterface $sm) {
                    return new Session(
                        $sm->get('doctrine.entitymanager.orm_default')
                    );
                },
                'user_mapper_loginattempt' => function (ServiceLocatorInterface $sm) {
                    return new LoginAttempt(
                        $sm->get('doctrine.entitymanager.orm_default')
                    );
                },

                'user_mail_transport' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('config');
                    $config = $config['email'];
                    $class = '\Laminas\Mail\Transport\\' . $config['transport'];
                    $optionsClass = '\Laminas\Mail\Transport\\' . $config['transport'] . 'Options';
                    $transport = new $class();
                    $transport->setOptions(new $optionsClass($config['options']));

                    return $transport;
                },
                'user_auth_adapter' => function (ServiceLocatorInterface $sm) {
                    $adapter = new Mapper(
                        $sm->get('user_bcrypt'),
                        $sm->get('application_service_legacy'),
                        $sm->get('user_service_loginattempt')
                    );
                    $adapter->setMapper($sm->get('user_mapper_user'));

                    return $adapter;
                },
                'user_pin_auth_adapter' => function (ServiceLocatorInterface $sm) {
                    $adapter = new PinMapper(
                        $sm->get('application_service_legacy'),
                        $sm->get('user_service_loginattempt')
                    );
                    $adapter->setMapper($sm->get('user_mapper_user'));

                    return $adapter;
                },
                'user_auth_service' => function (ServiceLocatorInterface $sm) {
                    return new Authentication\AuthenticationService(
                        $sm->get('user_auth_storage'),
                        $sm->get('user_auth_adapter')
                    );
                },
                'user_pin_auth_service' => function (ServiceLocatorInterface $sm) {
                    return new AuthenticationService(
                        $sm->get('user_auth_storage'),
                        $sm->get('user_pin_auth_adapter')
                    );
                },
                'user_remoteaddress' => function (ServiceLocatorInterface $sm) {
                    $remote = new RemoteAddress();
                    $isProxied = $sm->get('config')['proxy']['enabled'];
                    $trustedProxies = $sm->get('config')['proxy']['ip_addresses'];
                    $proxyHeader = $sm->get('config')['proxy']['header'];

                    $remote->setUseProxy($isProxied)
                        ->setTrustedProxies($trustedProxies)
                        ->setProxyHeader($proxyHeader);

                    return $remote->getIpAddress();
                },
                'user_role' => function (ServiceLocatorInterface $sm) {
                    $authService = $sm->get('user_auth_service');
                    if ($authService->hasIdentity()) {
                        return $authService->getIdentity();
                    }
                    // TODO: Refactor and re-enable the ApiUser service after a circular dependency has been removed.
                    // Possibly extend the LaminasAuthService ?
//                    $apiService = $sm->get('user_service_apiuser');
//                    if ($apiService->hasIdentity()) {
//                        return 'apiuser';
//                    }
                    $range = $sm->get('config')['tue_range'];
                    if (0 === strpos($sm->get('user_remoteaddress'), $range)) {
                        return 'tueguest';
                    }

                    return 'guest';
                },
                'acl' => function (ServiceLocatorInterface $sm) {
                    // initialize the ACL
                    $acl = new Acl();

                    /*
                     * Define all basic roles.
                     *
                     * - guest: everyone gets at least this access level
                     * - tueguest: guest from the TU/e
                     * - user: GEWIS-member
                     * - apiuser: Automated tool given access by an admin
                     * - admin: Defined administrators
                     * - photo_guest: Special role for non-members but friends of GEWIS nonetheless
                     */
                    $acl->addRole(new Role('guest'));
                    $acl->addRole(new Role('tueguest'), 'guest');
                    $acl->addRole(new Role('user'), 'tueguest');
                    $acl->addrole(new Role('apiuser'), 'guest');
                    $acl->addrole(new Role('sosuser'), 'apiuser');
                    $acl->addrole(new Role('active_member'), 'user');
                    $acl->addrole(new Role('company_admin'), 'active_member');
                    $acl->addRole(new Role('admin'));
                    $acl->addRole(new Role('photo_guest'), 'guest');

                    $user = $sm->get('user_role');

                    // add user to registry
                    if ($user instanceof User) {
                        $roles = $user->getRoleNames();
                        // if the user has no roles, add the 'user' role by default
                        if (empty($roles)) {
                            $roles = ['user'];
                        }

                        if (count($user->getMember()->getCurrentOrganInstallations()) > 0) {
                            $roles[] = 'active_member';
                        }

                        $acl->addRole($user, $roles);
                    }

                    // admins are allowed to do everything
                    $acl->allow('admin');

                    // board members also are admins
                    $acl->allow('user', null, null, new IsBoardMember());

                    // configure the user ACL
                    $acl->addResource(new Resource('apiuser'));
                    $acl->addResource(new Resource('user'));

                    $acl->allow('user', 'user', ['password_change']);
                    $acl->allow('photo_guest', 'user', ['password_change']);
                    $acl->allow('tueguest', 'user', 'pin_login');

                    // sosusers can't do anything
                    $acl->deny('sosuser');

                    return $acl;
                },
            ],
            'shared' => [
                'user_role' => false,
            ],
        ];
    }
}
