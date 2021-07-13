<?php

namespace Education;

use Doctrine\Laminas\Hydrator\DoctrineObject;
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
use Laminas\ServiceManager\ServiceLocatorInterface;

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
        return include __DIR__.'/config/module.config.php';
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
                'education_service_exam' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('education_acl');
                    $storageService = $sm->get('application_service_storage');
                    $courseMapper = $sm->get('education_mapper_course');
                    $examMapper = $sm->get('education_mapper_exam');
                    $addCourseForm = $sm->get('education_form_add_course');
                    $searchCourseForm = $sm->get('education_form_searchcourse');
                    $tempUploadForm = $sm->get('education_form_tempupload');
                    $bulkSummaryForm = $sm->get('education_form_bulk_summary');
                    $bulkExamForm = $sm->get('education_form_bulk_exam');
                    $config = $sm->get('config');

                    return new Service\Exam(
                        $translator,
                        $userRole,
                        $acl,
                        $storageService,
                        $courseMapper,
                        $examMapper,
                        $addCourseForm,
                        $searchCourseForm,
                        $tempUploadForm,
                        $bulkSummaryForm,
                        $bulkExamForm,
                        $config
                    );
                },
                'education_form_tempupload' => function (ServiceLocatorInterface $sm) {
                    return new TempUpload(
                        $sm->get('translator')
                    );
                },
                'education_form_summaryupload' => function (ServiceLocatorInterface $sm) {
                    $form = new SummaryUpload(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('education_hydrator'));

                    return $form;
                },
                'education_form_add_course' => function (ServiceLocatorInterface $sm) {
                    return new AddCourse(
                        $sm->get('translator')
                    );
                },
                'education_form_bulk_exam' => function (ServiceLocatorInterface $sm) {
                    return new Bulk(
                        $sm->get('translator'),
                        $sm->get('education_form_fieldset_exam')
                    );
                },
                'education_form_bulk_summary' => function (ServiceLocatorInterface $sm) {
                    return new Bulk(
                        $sm->get('translator'),
                        $sm->get('education_form_fieldset_summary')
                    );
                },
                'education_form_searchcourse' => function (ServiceLocatorInterface $sm) {
                    return new SearchCourse(
                        $sm->get('translator')
                    );
                },
                'education_form_fieldset_exam' => function (ServiceLocatorInterface $sm) {
                    $fieldset = new Form\Fieldset\Exam(
                        $sm->get('translator')
                    );
                    $fieldset->setConfig($sm->get('config'));
                    $fieldset->setObject(new Model\Exam());
                    $fieldset->setHydrator($sm->get('education_hydrator_exam'));

                    return $fieldset;
                },
                'education_form_fieldset_summary' => function (ServiceLocatorInterface $sm) {
                    $fieldset = new Form\Fieldset\Summary(
                        $sm->get('translator')
                    );
                    $fieldset->setConfig($sm->get('config'));
                    $fieldset->setObject(new Summary());
                    $fieldset->setHydrator($sm->get('education_hydrator'));

                    return $fieldset;
                },
                'education_mapper_exam' => function (ServiceLocatorInterface $sm) {
                    return new Exam(
                        $sm->get('education_doctrine_em')
                    );
                },
                'education_mapper_course' => function (ServiceLocatorInterface $sm) {
                    return new Course(
                        $sm->get('education_doctrine_em')
                    );
                },
                'education_mapper_study' => function (ServiceLocatorInterface $sm) {
                    return new Study(
                        $sm->get('education_doctrine_em')
                    );
                },
                'education_hydrator_study' => function (ServiceLocatorInterface $sm) {
                    return new DoctrineObject(
                        $sm->get('education_doctrine_em'),
                        'Education\Model\Study'
                    );
                },
                'education_hydrator_course' => function (ServiceLocatorInterface $sm) {
                    return new DoctrineObject(
                        $sm->get('education_doctrine_em'),
                        'Education\Model\Course'
                    );
                },
                'education_hydrator' => function (ServiceLocatorInterface $sm) {
                    return new DoctrineObject(
                        $sm->get('education_doctrine_em')
                    );
                },
                'education_hydrator_exam' => function (ServiceLocatorInterface $sm) {
                    return new DoctrineObject(
                        $sm->get('education_doctrine_em'),
                        'Education\Model\Exam'
                    );
                },
                'education_acl' => function (ServiceLocatorInterface $sm) {
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
                'education_doctrine_em' => function (ServiceLocatorInterface $sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                },
            ],
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
                'examUrl' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('config');
                    $helper = new ExamUrl();
                    $helper->setDir($config['education']['public_dir']);
                    $helper->setExamService($sm->get('education_service_exam'));

                    return $helper;
                },
            ],
        ];
    }
}
