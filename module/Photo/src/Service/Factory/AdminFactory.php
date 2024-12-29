<?php

declare(strict_types=1);

namespace Photo\Service\Factory;

use Application\Service\FileStorage as FileStorageService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Mapper\Photo as PhotoMapper;
use Photo\Service\AclService;
use Photo\Service\Admin as AdminService;
use Photo\Service\Metadata as MetadataService;
use Photo\Service\Photo as PhotoService;
use Psr\Container\ContainerInterface;

class AdminFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdminService {
        return new AdminService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(PhotoService::class),
            $container->get(MetadataService::class),
            $container->get(FileStorageService::class),
            $container->get(PhotoMapper::class),
        );
    }
}
