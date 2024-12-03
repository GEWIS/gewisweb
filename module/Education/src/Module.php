<?php

declare(strict_types=1);

namespace Education;

use Application\Form\Factory\BaseFormFactory;
use Application\Hydrator\Strategy\LanguageHydratorStrategy;
use Application\Mapper\Factory\BaseMapperFactory;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Education\Form\Bulk as BulkForm;
use Education\Form\Course as CourseForm;
use Education\Form\Factory\CourseFactory as CourseFormFactory;
use Education\Form\Fieldset\Exam as ExamFieldset;
use Education\Form\Fieldset\Factory\ExamFactory as ExamFieldsetFactory;
use Education\Form\Fieldset\Factory\SummaryFactory as SummaryFieldsetFactory;
use Education\Form\Fieldset\Summary as SummaryFieldset;
use Education\Form\SearchCourse as SearchCourseForm;
use Education\Form\TempUpload as TempUploadForm;
use Education\Mapper\Course as CourseMapper;
use Education\Mapper\CourseDocument as CourseDocumentMapper;
use Education\Service\AclService;
use Education\Service\Course as CourseService;
use Education\Service\Factory\CourseFactory as CourseServiceFactory;
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
                // Services
                AclService::class => AclServiceFactory::class,
                CourseService::class => CourseServiceFactory::class,
                // Mappers
                CourseDocumentMapper::class => BaseMapperFactory::class,
                CourseMapper::class => BaseMapperFactory::class,
                // Forms
                CourseForm::class => CourseFormFactory::class,
                SearchCourseForm::class => BaseFormFactory::class,
                TempUploadForm::class => BaseFormFactory::class,
                'education_form_bulk_exam' => static function (ContainerInterface $container) {
                    return new BulkForm(
                        $container->get(MvcTranslator::class),
                        $container->get(CourseMapper::class),
                        $container->get(ExamFieldset::class),
                    );
                },
                'education_form_bulk_summary' => static function (ContainerInterface $container) {
                    return new BulkForm(
                        $container->get(MvcTranslator::class),
                        $container->get(CourseMapper::class),
                        $container->get(SummaryFieldset::class),
                    );
                },
                ExamFieldset::class => ExamFieldsetFactory::class,
                SummaryFieldset::class => SummaryFieldsetFactory::class,
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
                // Commands
                // N/A
            ],
        ];
    }
}
