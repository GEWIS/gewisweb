<?php
namespace Decision;


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
                'decision_service_organ' => 'Decision\Service\Organ',
                'decision_service_member' => 'Decision\Service\Member'
            ),
            'factories' => array(
                'decision_mapper_member' => function ($sm) {
                    return new \Decision\Mapper\Member(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_mapper_organ' => function ($sm) {
                    return new \Decision\Mapper\Organ(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_acl' => function ($sm) {
                    $acl = $sm->get('acl');

                    // add resources for this module
                    $acl->addResource('organ');
                    $acl->addResource('member');

                    // users are allowed to view the organs
                    $acl->allow('user', 'organ', 'view');
                    $acl->allow('user', 'organ', 'show');
                    $acl->allow('user', 'member', 'view');

                    return $acl;
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'decision_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            )
        );
    }
}
