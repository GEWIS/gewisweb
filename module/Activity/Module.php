<?php
namespace Activity;


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
            'invokables' => array(
                'activity_service_activity' => 'Activity\Service\Activity'
            ),
            'factories' => array(
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'activity_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                },
                'activity_service_signup' => function ($sm) {
                    $ac = new Service\Signup();
                    $ac->setServiceManager($sm);
                    return $ac;
                },
		'activity_service_signoff' => function ($sm) {
                    $ac = new Service\Signup();
                    $ac->setServiceManager($sm);
                    return $ac;
                },
                'activity_mapper_activity' => function ($sm) {
                    return new \Activity\Mapper\Activity(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_mapper_activity_field_value' => function ($sm) {
                    return new \Activity\Mapper\ActivityFieldValue(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_mapper_activity_option' => function ($sm) {
                    return new \Activity\Mapper\ActivityOption(
                        $sm->get('activity_doctrine_em')
                    );
                },        
                'activity_mapper_signup' => function ($sm) {
                    return new \Activity\Mapper\Signup(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_acl' => function ($sm) {
                    $acl = $sm->get('acl');
                    $acl->addResource('activity');
                    $acl->addResource('activitySignup');

                    $acl->allow('guest', 'activity', 'view');
                    $acl->allow('guest', 'activitySignup', 'signUp');
                    $acl->allow('guest', 'activitySignup', 'view');

                    $acl->allow('user', 'activity', 'create');

                    return $acl;
                },
            )
        );
    }
}