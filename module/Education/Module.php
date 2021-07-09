<?php

namespace Education;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Education\Form\AddCourse;
use Education\Form\Bulk;
use Education\Form\SearchCourse;
use Education\Form\SummaryUpload;
use Education\Form\TempUpload;
use Education\Mapper\Course;
use Education\Mapper\Exam;
use Education\Mapper\Study;
use Education\Model\Summary;
use Education\View\Helper\ExamUrl;

class Module
{
    /**
     * Get the autoloader configuration.
     */
    public function getAutoloaderConfig()
    {
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
            'factories' => [
                'education_service_exam' => function ($sm) {
                    $translator = $sm->get('translator');
                    return new Service\Exam($translator);
                },
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
                        $sm->get('translator'),
                        $sm->get('education_form_fieldset_exam')
                    );
                },
                'education_form_bulk_summary' => function ($sm) {
                    return new Bulk(
                        $sm->get('translator'),
                        $sm->get('education_form_fieldset_summary')
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
                    return new Course(
                        $sm->get('education_doctrine_em')
                    );
                },
                'education_mapper_study' => function ($sm) {
                    return new Study(
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
