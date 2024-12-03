<?php

declare(strict_types=1);

namespace Photo\Controller\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\TagController;
use Photo\Service\AclService;
use Photo\Service\Photo as PhotoService;
use Psr\Container\ContainerInterface;

class TagControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): TagController {
        return new TagController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(PhotoService::class),
        );
    }
}
