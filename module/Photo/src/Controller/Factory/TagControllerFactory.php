<?php

declare(strict_types=1);

namespace Photo\Controller\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\TagController;
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
            $container->get('photo_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('photo_service_photo'),
        );
    }
}
