<?php
namespace User;

use User\Service\ApiApp;
use User\Service\Factory\ApiAppFactory;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;
use Zend\Mvc\MvcEvent;
use Zend\Http\Request as HttpRequest;

use User\Permissions\NotAllowedException;
use User\Model\User;

class Module
{

    /**
     * Bootstrap.
     *
     * @var MvcEvent $e
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
        $em->attach(MvcEvent::EVENT_DISPATCH_ERROR, function($e) {
            if (($e->getError() == 'error-exception') &&
                    ($e->getParam('exception', null) != null) &&
                    ($e->getParam('exception') instanceof NotAllowedException)) {
                $form = $e->getApplication()->getServiceManager()->get('user_form_login');
                $e->getResult()->setVariable('form', $form);
                $e->getResult()->setTemplate((APP_ENV === 'production' ? 'error/403' : 'error/debug/403'));
                $e->getResponse()->setStatusCode(403);
            }
        }, -100);
    }


    /**
     * Get the autoloader configuration.
     *
     * @return array Autoloader config
     */
    public function getAutoloaderConfig()
    {
        if (APP_ENV === 'production') {
            return [
                'Zend\Loader\ClassMapAutoloader' => [
                    __DIR__ . '/autoload_classmap.php',
                ]
            ];
        }

        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ]
            ]
        ];
    }

    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
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
                'Zend\Authentication\AuthenticationService' => 'user_auth_service'
            ],

            'invokables' => [
                'user_service_user' => 'User\Service\User',
                'user_service_apiuser' => 'User\Service\ApiUser',
                'user_service_email' => 'User\Service\Email',
            ],

            'factories' => [
                'ApiApp' => 'ApiAppFactory',
                '\User\Mapper\ApiApp' => '\User\Mapper\Factory\ApiAppFactory',
                'user_auth_storage' => function ($sm) {
                    return new \User\Authentication\Storage\Session(
                        $sm
                    );
                },
                'user_bcrypt' => function ($sm) {
                    $bcrypt = new \Zend\Crypt\Password\Bcrypt();
                    $config = $sm->get('config');
                    $bcrypt->setCost($config['bcrypt_cost']);
                    return $bcrypt;
                },

                'user_hydrator' => function ($sm) {
                    return new \DoctrineModule\Stdlib\Hydrator\DoctrineObject(
                        $sm->get('user_doctrine_em')
                    );
                },
                'user_form_activate' => function ($sm) {
                    return new \User\Form\Activate(
                        $sm->get('translator')
                    );
                },
                'user_form_register' => function ($sm) {
                    return new \User\Form\Register(
                        $sm->get('translator')
                    );
                },
                'user_form_login' => function ($sm) {
                    return new \User\Form\Login(
                        $sm->get('translator')
                    );
                },
                'user_form_password' => function ($sm) {
                    return new \User\Form\Password(
                        $sm->get('translator')
                    );
                },
                'user_form_passwordreset' => function($sm) {
                    return new \User\Form\Register(
                        $sm->get('translator')
                    );
                },
                'user_form_passwordactivate' => function($sm) {
                    return new \User\Form\Activate(
                        $sm->get('translator')
                    );
                },
                'user_form_apitoken' => function ($sm) {
                    $form = new \User\Form\ApiToken(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('user_hydrator'));
                    return $form;
                },

                'user_mapper_user' => function ($sm) {
                    return new \User\Mapper\User(
                        $sm->get('user_doctrine_em')
                    );
                },
                'user_mapper_newuser' => function ($sm) {
                    return new \User\Mapper\NewUser(
                        $sm->get('user_doctrine_em')
                    );
                },
                'user_mapper_apiuser' => function($sm) {
                    return new \User\Mapper\ApiUser(
                        $sm->get('user_doctrine_em')
                    );
                },
                'user_mapper_session' => function($sm) {
                    return new \User\Mapper\Session(
                        $sm->get('user_doctrine_em')
                    );
                },
                'user_mapper_loginattempt' => function($sm) {
                    return new \User\Mapper\LoginAttempt(
                        $sm->get('user_doctrine_em')
                    );
                },

                'user_mail_transport' => function ($sm) {
                    $config = $sm->get('config');
                    $config = $config['email'];
                    $class = '\Zend\Mail\Transport\\' . $config['transport'];
                    $optionsClass = '\Zend\Mail\Transport\\' . $config['transport'] . 'Options';
                    $transport = new $class();
                    $transport->setOptions(new $optionsClass($config['options']));
                    return $transport;
                },
                'user_auth_adapter' => function ($sm) {
                    $adapter = new \User\Authentication\Adapter\Mapper(
                        $sm->get('user_bcrypt'),
                        $sm->get('application_service_legacy'),
                        $sm->get('user_service_user')
                    );
                    $adapter->setMapper($sm->get('user_mapper_user'));
                    return $adapter;
                },
                'user_pin_auth_adapter' => function ($sm) {
                    $adapter = new \User\Authentication\Adapter\PinMapper(
                        $sm->get('application_service_legacy'),
                        $sm->get('user_service_user')
                    );
                    $adapter->setMapper($sm->get('user_mapper_user'));
                    return $adapter;
                },
                'user_auth_service' => function ($sm) {
                    return new \User\Authentication\AuthenticationService(
                        $sm->get('user_auth_storage'),
                        $sm->get('user_auth_adapter')
                    );
                },
                'user_pin_auth_service' => function ($sm) {
                    return new \Zend\Authentication\AuthenticationService(
                        $sm->get('user_auth_storage'),
                        $sm->get('user_pin_auth_adapter')
                    );
                },
                'user_remoteaddress' => function ($sm) {
                    $remote = new \Zend\Http\PhpEnvironment\RemoteAddress();
                    return $remote->getIpAddress();
                },
                'user_role' => function ($sm) {
                    $authService = $sm->get('user_auth_service');
                    if ($authService->hasIdentity()) {
                        return $authService->getIdentity();
                    }
                    $apiService = $sm->get('user_service_apiuser');
                    if ($apiService->hasIdentity()) {
                        return 'apiuser';
                    }
                    $range = $sm->get('config')['tue_range'];
                    if (strpos($sm->get('user_remoteaddress'), $range) === 0) {
                        return 'tueguest';
                    }
                    return 'guest';
                },
                'acl' => function ($sm) {
                    // initialize the ACL
                    $acl = new Acl();

                    /**
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

                        // TODO: change this to getActiveOrganInstalltions() once 529 is fixed
                        if (count($user->getMember()->getOrganInstallations()) > 0) {
                            $roles[] = 'active_member';
                        }

                        $acl->addRole($user, $roles);
                    }

                    // admins are allowed to do everything
                    $acl->allow('admin');

                    // board members also are admins
                    $acl->allow('user', null, null, new \User\Permissions\Assertion\IsBoardMember());

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
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'user_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            ],
            'shared' => [
                'user_role' => false
            ]
        ];
    }
}
