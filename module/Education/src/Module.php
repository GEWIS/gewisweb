<?php

declare(strict_types=1);

namespace Education;

use Application\Hydrator\Strategy\LanguageHydratorStrategy;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Education\Form\Bulk as BulkForm;
use Education\Form\Course as CourseForm;
use Education\Form\Fieldset\Exam as ExamFieldset;
use Education\Form\Fieldset\Summary as SummaryFieldset;
use Education\Form\SearchCourse as SearchCourseForm;
use Education\Form\TempUpload as TempUploadForm;
use Education\Hydrator\Strategy\ExamTypeHydratorStrategy;
use Education\Mapper\Course as CourseMapper;
use Education\Mapper\CourseDocument as CourseDocumentMapper;
use Education\Model\Exam as ExamModel;
use Education\Model\Summary as SummaryModel;
use Education\Service\Exam as ExamService;
use Education\View\Helper\ExamUrl;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
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
                'education_service_exam' => static function (ContainerInterface $container) {
                    $aclService = $container->get('education_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $storageService = $container->get('application_service_storage');
                    $courseMapper = $container->get('education_mapper_course');
                    $courseDocumentMapper = $container->get('education_mapper_courseDocument');
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
                        $courseDocumentMapper,
                        $addCourseForm,
                        $tempUploadForm,
                        $bulkSummaryForm,
                        $bulkExamForm,
                        $config,
                    );
                },
                'education_form_tempupload' => static function (ContainerInterface $container) {
                    return new TempUploadForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'education_form_add_course' => static function (ContainerInterface $container) {
                    $courseForm = new CourseForm(
                        $container->get(MvcTranslator::class),
                        $container->get('education_mapper_course'),
                    );
                    $courseForm->setHydrator($container->get('education_hydrator'));

                    return $courseForm;
                },
                'education_form_bulk_exam' => static function (ContainerInterface $container) {
                    return new BulkForm(
                        $container->get(MvcTranslator::class),
                        $container->get('education_mapper_course'),
                        $container->get('education_form_fieldset_exam'),
                    );
                },
                'education_form_bulk_summary' => static function (ContainerInterface $container) {
                    return new BulkForm(
                        $container->get(MvcTranslator::class),
                        $container->get('education_mapper_course'),
                        $container->get('education_form_fieldset_summary'),
                    );
                },
                'education_form_searchcourse' => static function (ContainerInterface $container) {
                    return new SearchCourseForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'education_form_fieldset_exam' => static function (ContainerInterface $container) {
                    $fieldset = new ExamFieldset(
                        $container->get(MvcTranslator::class),
                    );
                    $fieldset->setConfig($container->get('config'));
                    $fieldset->setObject(new ExamModel());
                    $hydrator = $container->get('education_hydrator_document');
                    $hydrator->addStrategy('examType', new ExamTypeHydratorStrategy());
                    $fieldset->setHydrator($hydrator);

                    return $fieldset;
                },
                'education_form_fieldset_summary' => static function (ContainerInterface $container) {
                    $fieldset = new SummaryFieldset(
                        $container->get(MvcTranslator::class),
                    );
                    $fieldset->setConfig($container->get('config'));
                    $fieldset->setObject(new SummaryModel());
                    $fieldset->setHydrator($container->get('education_hydrator_document'));

                    return $fieldset;
                },
                'education_mapper_courseDocument' => static function (ContainerInterface $container) {
                    return new CourseDocumentMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'education_mapper_course' => static function (ContainerInterface $container) {
                    return new CourseMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'education_hydrator' => static function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                        false,
                    );
                },
                'education_hydrator_document' => static function (ContainerInterface $container) {
                    $hydrator = new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                        false,
                    );
                    $hydrator->addStrategy('language', new LanguageHydratorStrategy());

                    return $hydrator;
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
                'examUrl' => static function (ContainerInterface $container) {
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
