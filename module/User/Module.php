<?php
namespace User;

use Zend\Permissions\Acl\Acl;

class Module
{

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
                'user_service_user' => 'User\Service\User'
            ),
            'factories' => array(
                'user_bcrypt' => function ($sm) {
                    $bcrypt = new \Zend\Crypt\Password\Bcrypt();
                    // TODO: set cost
                    return $bcrypt;
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
                'acl' => function ($sm) {
                    // initialize the ACL
                    $acl = new Acl();

                    // define basic roles
                    $acl->addRole(new Role('guest')); // simple guest
                    $acl->addRole(new Role('user'), 'guest'); // simple user
                    $acl->addRole(new Role('admin')); // administrator

                    // TODO: add current user as role
                    // TODO: define resources and add permissions

                    return $acl;
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'user_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            )
        );
    }
}
