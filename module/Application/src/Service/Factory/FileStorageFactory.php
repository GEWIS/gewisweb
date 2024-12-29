<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\FileStorage as FileStorageService;
use Application\Service\Watermark;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class FileStorageFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): FileStorageService {
        return new FileStorageService(
            $container->get(MvcTranslator::class),
            $container->get('config')['storage'],
            $container->get(Watermark::class),
        );
    }
}
