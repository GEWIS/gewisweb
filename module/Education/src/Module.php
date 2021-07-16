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
use Interop\Container\ContainerInterface;
use User\Authorization\AclServiceFactory;

class Module
{
    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
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
                'education_service_exam' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $storageService = $container->get('application_service_storage');
                    $courseMapper = $container->get('education_mapper_course');
                    $examMapper = $container->get('education_mapper_exam');
                    $addCourseForm = $container->get('education_form_add_course');
                    $searchCourseForm = $container->get('education_form_searchcourse');
                    $tempUploadForm = $container->get('education_form_tempupload');
                    $bulkSummaryForm = $container->get('education_form_bulk_summary');
                    $bulkExamForm = $container->get('education_form_bulk_exam');
                    $config = $container->get('config');
                    $aclService = $container->get('education_service_acl');

                    return new Service\Exam(
                        $translator,
                        $storageService,
                        $courseMapper,
                        $examMapper,
                        $addCourseForm,
                        $searchCourseForm,
                        $tempUploadForm,
                        $bulkSummaryForm,
                        $bulkExamForm,
                        $config,
                        $aclService
                    );
                },
                'education_form_tempupload' => function (ContainerInterface $container) {
                    return new TempUpload(
                        $container->get('translator')
                    );
                },
                'education_form_summaryupload' => function (ContainerInterface $container) {
                    $form = new SummaryUpload(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('education_hydrator'));

                    return $form;
                },
                'education_form_add_course' => function (ContainerInterface $container) {
                    return new AddCourse(
                        $container->get('translator')
                    );
                },
                'education_form_bulk_exam' => function (ContainerInterface $container) {
                    return new Bulk(
                        $container->get('translator'),
                        $container->get('education_form_fieldset_exam')
                    );
                },
                'education_form_bulk_summary' => function (ContainerInterface $container) {
                    return new Bulk(
                        $container->get('translator'),
                        $container->get('education_form_fieldset_summary')
                    );
                },
                'education_form_searchcourse' => function (ContainerInterface $container) {
                    return new SearchCourse(
                        $container->get('translator')
                    );
                },
                'education_form_fieldset_exam' => function (ContainerInterface $container) {
                    $fieldset = new Form\Fieldset\Exam(
                        $container->get('translator')
                    );
                    $fieldset->setConfig($container->get('config'));
                    $fieldset->setObject(new Model\Exam());
                    $fieldset->setHydrator($container->get('education_hydrator_exam'));

                    return $fieldset;
                },
                'education_form_fieldset_summary' => function (ContainerInterface $container) {
                    $fieldset = new Form\Fieldset\Summary(
                        $container->get('translator')
                    );
                    $fieldset->setConfig($container->get('config'));
                    $fieldset->setObject(new Summary());
                    $fieldset->setHydrator($container->get('education_hydrator'));

                    return $fieldset;
                },
                'education_mapper_exam' => function (ContainerInterface $container) {
                    return new Exam(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'education_mapper_course' => function (ContainerInterface $container) {
                    return new Course(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'education_mapper_study' => function (ContainerInterface $container) {
                    return new Study(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'education_hydrator_study' => function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                        'Education\Model\Study'
                    );
                },
                'education_hydrator_course' => function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                        'Education\Model\Course'
                    );
                },
                'education_hydrator' => function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'education_hydrator_exam' => function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                        'Education\Model\Exam'
                    );
                },
                'education_service_acl' => AclServiceFactory::class,
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
                'examUrl' => function (ContainerInterface $container) {
                    $config = $container->get('config');
                    $helper = new ExamUrl();
                    $helper->setDir($config['education']['public_dir']);
                    $helper->setExamService($container->get('education_service_exam'));

                    return $helper;
                },
            ],
        ];
    }
}
