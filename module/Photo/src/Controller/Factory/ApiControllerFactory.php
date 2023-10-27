<?php

declare(strict_types=1);

namespace Photo\Controller\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\ApiController;
use Psr\Container\ContainerInterface;

class ApiControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiController {
        return new ApiController(
            $container->get('photo_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('photo_mapper_tag'),
            $container->get('photo_mapper_vote'),
            $container->get('photo_service_album'),
        );
    }
}
