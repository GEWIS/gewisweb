<?php
namespace Company;


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
                'company_service_company' => 'Company\Service\Company'
            ),
            'factories' => array(

                'company_mapper_company' => function ($sm) {
                    return new \Company\Mapper\Company(
                        $sm->get('company_doctrine_em')
                    );
                },
                    'company_mapper_job' => function ($sm) {
                        return new \Company\Mapper\Job(
                            $sm->get('company_doctrine_em')
                        );
                    },
                        'company_doctrine_em' => function ($sm) {
                            return $sm->get('doctrine.entitymanager.orm_default');
                        }
        )
        );
    }
}
