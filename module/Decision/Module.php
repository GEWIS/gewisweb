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
                'decision_service_organ' => 'Decision\Service\Organ'
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
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'decision_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            )
        );
    }
}
