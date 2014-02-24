<?php
namespace Education;

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
                'education_service_exam' => 'Education\Service\Exam',
                'education_service_oase' => 'Education\Service\Oase'
            ),
            'factories' => array(
                'education_form_searchcourse' => function ($sm) {
                    return new \Education\Form\SearchCourse(
                        $sm->get('translator')
                    );
                },
                'education_mapper_study' => function ($sm) {
                    return new \Education\Mapper\Study(
                        $sm->get('education_doctrine_em')
                    );
                },
                'education_hydrator_study' => function ($sm) {
                    return new \DoctrineModule\Stdlib\Hydrator\DoctrineObject(
                        $sm->get('education_doctrine_em'),
                        'Education\Model\Study'
                    );
                },
                'education_oase_soapclient' => function ($sm) {
                    $config = $sm->get('config');
                    $config = $config['oase']['soap'];
                    return new \Zend\Soap\Client(
                        $config['wsdl'], $config['options']
                    );
                },
                'education_oase_client' => function ($sm) {
                    return new \Education\Oase\Client(
                        $sm->get('education_oase_soapclient')
                    );
                },
                'education_oase_service_course' => function ($sm) {
                    return new \Education\Oase\Service\Course(
                        $sm->get('education_oase_client')
                    );
                },
                'education_oase_service_study' => function ($sm) {
                    $service = new \Education\Oase\Service\Study(
                        $sm->get('education_oase_client')
                    );
                    $config = $sm->get('config');
                    $config = $config['oase']['studies'];
                    $service->setKeywords($config['keywords']);
                    $service->setNegativeKeywords($config['negative_keywords']);
                    $service->setGroupIds($config['group_ids']);
                    $service->setEducationTypes($config['education_types']);
                    $service->setHydrator($sm->get('education_hydrator_study'));
                    return $service;
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'education_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            )
        );
    }
}
