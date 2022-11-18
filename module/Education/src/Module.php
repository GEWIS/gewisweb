<?php

namespace Education;

use Doctrine\Laminas\Hydrator\DoctrineObject;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Education\Form\{
    Course as CourseForm,
    Bulk as BulkForm,
    Fieldset\Exam as ExamFieldset,
    Fieldset\Summary as SummaryFieldset,
    SearchCourse as SearchCourseForm,
    SummaryUpload as SummaryUploadForm,
    TempUpload as TempUploadForm,
};
use Education\Mapper\{
    Course as CourseMapper,
    Exam as ExamMapper,
};
use Education\Model\{
    Exam as ExamModel,
    Summary as SummaryModel,
};
use Education\Service\Exam as ExamService;
use Education\View\Helper\ExamUrl;
use Psr\Container\ContainerInterface;
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
                    $aclService = $container->get('education_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $storageService = $container->get('application_service_storage');
                    $courseMapper = $container->get('education_mapper_course');
                    $examMapper = $container->get('education_mapper_exam');
                    $addCourseForm = $container->get('education_form_add_course');
                    $tempUploadForm = $container->get('education_form_tempupload');
                    $bulkSummaryForm = $container->get('education_form_bulk_summary');
                    $bulkExamForm = $container->get('education_form_bulk_exam');
                    $config = $container->get('config');

                    return new ExamService(
                        $aclService,
                        $translator,
                        $storageService,
                        $courseMapper,
                        $examMapper,
                        $addCourseForm,
                        $tempUploadForm,
                        $bulkSummaryForm,
                        $bulkExamForm,
                        $config,
                    );
                },
                'education_form_tempupload' => function (ContainerInterface $container) {
                    return new TempUploadForm(
                        $container->get(MvcTranslator::class)
                    );
                },
                'education_form_summaryupload' => function (ContainerInterface $container) {
                    $form = new SummaryUploadForm(
                        $container->get(MvcTranslator::class)
                    );
                    $form->setHydrator($container->get('education_hydrator'));

                    return $form;
                },
                'education_form_add_course' => function (ContainerInterface $container) {
                    $courseForm = new CourseForm(
                        $container->get(MvcTranslator::class),
                        $container->get('education_mapper_course'),
                    );
                    $courseForm->setHydrator($container->get('education_hydrator'));

                    return $courseForm;
                },
                'education_form_bulk_exam' => function (ContainerInterface $container) {
                    return new BulkForm(
                        $container->get(MvcTranslator::class),
                        $container->get('education_form_fieldset_exam')
                    );
                },
                'education_form_bulk_summary' => function (ContainerInterface $container) {
                    return new BulkForm(
                        $container->get(MvcTranslator::class),
                        $container->get('education_form_fieldset_summary')
                    );
                },
                'education_form_searchcourse' => function (ContainerInterface $container) {
                    return new SearchCourseForm(
                        $container->get(MvcTranslator::class)
                    );
                },
                'education_form_fieldset_exam' => function (ContainerInterface $container) {
                    $fieldset = new ExamFieldset(
                        $container->get(MvcTranslator::class)
                    );
                    $fieldset->setConfig($container->get('config'));
                    $fieldset->setObject(new ExamModel());
                    $fieldset->setHydrator($container->get('education_hydrator'));

                    return $fieldset;
                },
                'education_form_fieldset_summary' => function (ContainerInterface $container) {
                    $fieldset = new SummaryFieldset(
                        $container->get(MvcTranslator::class)
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
                'education_hydrator' => function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                        false,
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
