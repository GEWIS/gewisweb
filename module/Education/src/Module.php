<?php

namespace Education;

use Doctrine\Laminas\Hydrator\DoctrineObject;
use Education\Form\{
    AddCourse as AddCourseForm,
    Bulk as BulkForm,
    SearchCourse as SearchCourseForm,
    SummaryUpload as SummaryUploadForm,
    TempUpload as TempUploadForm,
};
use Education\Mapper\{
    Course as CourseMapper,
    Exam as ExamMapper,
    Study as StudyMapper,
};
use Education\Model\{
    Exam as ExamModel,
    Summary as SummaryModel,
};
use Education\Service\Exam as ExamService;
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
    public function getServiceConfig(): array
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

                    return new ExamService(
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
                    return new TempUploadForm(
                        $container->get('translator')
                    );
                },
                'education_form_summaryupload' => function (ContainerInterface $container) {
                    $form = new SummaryUploadForm(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('education_hydrator'));

                    return $form;
                },
                'education_form_add_course' => function (ContainerInterface $container) {
                    return new AddCourseForm(
                        $container->get('translator')
                    );
                },
                'education_form_bulk_exam' => function (ContainerInterface $container) {
                    return new BulkForm(
                        $container->get('translator'),
                        $container->get('education_form_fieldset_exam')
                    );
                },
                'education_form_bulk_summary' => function (ContainerInterface $container) {
                    return new BulkForm(
                        $container->get('translator'),
                        $container->get('education_form_fieldset_summary')
                    );
                },
                'education_form_searchcourse' => function (ContainerInterface $container) {
                    return new SearchCourseForm(
                        $container->get('translator')
                    );
                },
                'education_form_fieldset_exam' => function (ContainerInterface $container) {
                    $fieldset = new Form\Fieldset\Exam(
                        $container->get('translator')
                    );
                    $fieldset->setConfig($container->get('config'));
                    $fieldset->setObject(new ExamModel());
                    $fieldset->setHydrator($container->get('education_hydrator_exam'));

                    return $fieldset;
                },
                'education_form_fieldset_summary' => function (ContainerInterface $container) {
                    $fieldset = new Form\Fieldset\Summary(
                        $container->get('translator')
                    );
                    $fieldset->setConfig($container->get('config'));
                    $fieldset->setObject(new SummaryModel());
                    $fieldset->setHydrator($container->get('education_hydrator'));

                    return $fieldset;
                },
                'education_mapper_exam' => function (ContainerInterface $container) {
                    return new ExamMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'education_mapper_course' => function (ContainerInterface $container) {
                    return new CourseMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'education_mapper_study' => function (ContainerInterface $container) {
                    return new StudyMapper(
                        $container->get('doctrine.entitymanager.orm_default')
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
    public function getViewHelperConfig(): array
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
