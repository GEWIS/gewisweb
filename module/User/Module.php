<?php
namespace User;


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
                'user_doctrine_em' => 'doctrine.entitymanager.orm_default'
            ),
            'invokables' => array(
                'user_service_user' => 'User\Service\User'
            ),
            'factories' => array(
                'user_form_login' => function ($sm) {
                    return new \User\Form\Login(
                        $sm->get('translator')
                    );
                },
                'user_mapper_user' => function ($sm) {
                    return new \User\Mapper\User(
                        $sm->get('user_doctrine_em')
                    );
                }
            )
        );
    }
}
