<?php

declare(strict_types=1);

namespace Education\Service\Factory;

use Application\Service\FileStorage as FileStorageService;
use Education\Form\Course as CourseForm;
use Education\Form\TempUpload as TempUploadForm;
use Education\Mapper\Course as CourseMapper;
use Education\Mapper\CourseDocument as CourseDocumentMapper;
use Education\Service\AclService;
use Education\Service\Course as CourseService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CourseFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): CourseService {
        return new CourseService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(FileStorageService::class),
            $container->get(CourseMapper::class),
            $container->get(CourseDocumentMapper::class),
            $container->get(CourseForm::class),
            $container->get(TempUploadForm::class),
            $container->get('education_form_bulk_summary'),
            $container->get('education_form_bulk_exam'),
            $container->get('config'),
        );
    }
}
