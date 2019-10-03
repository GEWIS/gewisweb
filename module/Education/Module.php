<?php
namespace Education;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Education\Form\AddCourse;
use Education\Form\Bulk;
use Education\Form\SearchCourse;
use Education\Form\SummaryUpload;
use Education\Form\TempUpload;
use Education\Mapper\Exam;
use Education\Model\Summary;
use Education\Oase\Client;
use Education\Oase\Service\Course;
use Education\Oase\Service\Study;
use Education\View\Helper\ExamUrl;

class Module
{

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
            'invokables' => [
                'education_service_exam' => 'Education\Service\Exam',
                'education_service_oase' => 'Education\Service\Oase'
            ],
            'factories' => [
                'education_form_tempupload' => function ($sm) {
                    return new TempUpload(
                        $sm->get('translator')
                    );
                },
                'education_form_summaryupload' => function ($sm) {
                    $form = new SummaryUpload(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('education_hydrator'));
                    return $form;
                },
                'education_form_add_course' => function ($sm) {
                    return new AddCourse(
                        $sm->get('translator')
                    );
                },
                'education_form_bulk_exam' => function ($sm) {
                    return new Bulk(
                        $sm->get('translator'), $sm->get('education_form_fieldset_exam')
                    );
                },
                'education_form_bulk_summary' => function ($sm) {
                    return new Bulk(
                        $sm->get('translator'), $sm->get('education_form_fieldset_summary')
                    );
                },
                'education_form_searchcourse' => function ($sm) {
                    return new SearchCourse(
                        $sm->get('translator')
                    );
                },
                'education_form_fieldset_exam' => function ($sm) {
                    $fieldset = new Form\Fieldset\Exam(
                        $sm->get('translator')
                    );
                    $fieldset->setConfig($sm->get('config'));
                    $fieldset->setObject(new Model\Exam());
                    $fieldset->setHydrator($sm->get('education_hydrator_exam'));
                    return $fieldset;
                },
                'education_form_fieldset_summary' => function ($sm) {
                    $fieldset = new Form\Fieldset\Summary(
                        $sm->get('translator')
                    );
                    $fieldset->setConfig($sm->get('config'));
                    $fieldset->setObject(new Summary());
                    $fieldset->setHydrator($sm->get('education_hydrator'));
                    return $fieldset;
                },
                'education_mapper_exam' => function ($sm) {
                    return new Exam(
                        $sm->get('education_doctrine_em')
                    );
                },
                'education_mapper_course' => function ($sm) {
                    return new Mapper\Course(
                        $sm->get('education_doctrine_em')
                    );
                },
                'education_mapper_study' => function ($sm) {
                    return new Mapper\Study(
                        $sm->get('education_doctrine_em')
                    );
                },
                'education_hydrator_study' => function ($sm) {
                    return new DoctrineObject(
                        $sm->get('education_doctrine_em'),
                        'Education\Model\Study'
                    );
                },
                'education_hydrator_course' => function ($sm) {
                    return new DoctrineObject(
                        $sm->get('education_doctrine_em'),
                        'Education\Model\Course'
                    );
                },
                'education_hydrator' => function ($sm) {
                    return new DoctrineObject(
                        $sm->get('education_doctrine_em')
                    );
                },
                'education_hydrator_exam' => function ($sm) {
                    return new DoctrineObject(
                        $sm->get('education_doctrine_em'),
                        'Education\Model\Exam'
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
                    return new Client(
                        $sm->get('education_oase_soapclient')
                    );
                },
                'education_oase_service_course' => function ($sm) {
                    $service = new Course(
                        $sm->get('education_oase_client')
                    );
                    $service->setHydrator($sm->get('education_hydrator_course'));
                    return $service;
                },
                'education_oase_service_study' => function ($sm) {
                    $service = new Study(
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
                'education_acl' => function ($sm) {
                    $acl = $sm->get('acl');

                    // add resource
                    $acl->addResource('exam');

                    // users (logged in GEWIS members) are allowed to view
                    // exams besides users, also people on the TU/e network are
                    // allowed to view and download exams (users inherit from
                    // tueguest)
                    $acl->allow('tueguest', 'exam', ['view', 'download']);

                    return $acl;
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'education_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            ]
        ];
    }

    /**
     * Get view helper configuration.
     *
     * @return array
     */
    public function getViewHelperConfig()
    {
        return [
            'factories' => [
                'examUrl' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $config = $locator->get('config');
                    $helper = new ExamUrl();
                    $helper->setDir($config['education']['public_dir']);
                    $helper->setExamService($locator->get('education_service_exam'));
                    return $helper;
                }
            ]
        ];
    }
}
