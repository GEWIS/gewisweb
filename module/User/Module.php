<?php
namespace User;

use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Mvc\MvcEvent;
use User\Permissions\NotAllowedException;

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

        // this event listener will turn the request into '403 Forbidden' when
        // there is a NotAllowedException
        $em->attach(MvcEvent::EVENT_DISPATCH_ERROR, function($e) {
            if (($e->getError() == 'error-exception') &&
                    ($e->getParam('exception', null) != null) &&
                    ($e->getParam('exception') instanceof NotAllowedException)) {
                $e->getResult()->setTemplate('error/403');
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
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                )
            )
        );
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
        return array(
            'aliases' => array(
                'Zend\Authentication\AuthenticationService' => 'user_auth_service'
            ),
            'invokables' => array(
                'user_auth_storage' => 'Zend\Authentication\Storage\Session',
                'user_service_user' => 'User\Service\User',
                'user_service_email' => 'User\Service\Email',
            ),
            'factories' => array(
                'user_bcrypt' => function ($sm) {
                    $bcrypt = new \Zend\Crypt\Password\Bcrypt();
                    $config = $sm->get('config');
                    $bcrypt->setCost($config['bcrypt_cost']);
                    return $bcrypt;
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
                'user_form_logout' => function ($sm) {
                    return new \User\Form\Logout(
                        $sm->get('translator')
                    );
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
                        $sm->get('user_bcrypt')
                    );
                    $adapter->setMapper($sm->get('user_mapper_user'));
                    return $adapter;
                },
                'user_auth_service' => function ($sm) {
                    return new \Zend\Authentication\AuthenticationService(
                        $sm->get('user_auth_storage'),
                        $sm->get('user_auth_adapter')
                    );
                },
                'user_role' => function ($sm) {
                    $authService = $sm->get('user_auth_service');
                    if ($authService->hasIdentity()) {
                        return $authService->getIdentity();
                    }
                    return 'guest';
                },
                'acl' => function ($sm) {
                    // initialize the ACL
                    $acl = new Acl();

                    // define basic roles
                    $acl->addRole(new Role('guest')); // simple guest
                    $acl->addRole(new Role('user'), 'guest'); // simple user
					$acl->addRole(new Role('activeMember')); // member who is in at least one organ
                    $acl->addRole(new Role('admin')); // administrator
					$acl->addResource('organ');

                    $user = $sm->get('user_role');

                    // add user to registry
                    if ('guest' != $user) {
					
                        // add organs to registry
						$organCollection = $user->getOrganRoleNames();
						
						foreach($organCollection as $organ){
							$roles[] = $organ->getAbbr();
							$acl->addRole(new Role($organ->getAbbr()), "activeMember");
							$acl->allow($organ->getAbbr(), "organ", "member");
						}
						
						//add functional roles to registery
						$roles = $user->getFunctionalRoleNames();
						$acl->addRole($user, $roles);
						
                    }

                    // admins are allowed to do everything
                    $acl->allow('admin');
					// be sure a admin is not a committee
					$acl->deny('admin', 'organ', 'member');

                    return $acl;
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'user_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            ),
            'shared' => array(
                'user_role' => false
            )
        );
    }
}
