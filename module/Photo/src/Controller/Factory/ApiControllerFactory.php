<?php

declare(strict_types=1);

namespace Photo\Controller\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\ApiController;
use Photo\Mapper\Tag as TagMapper;
use Photo\Mapper\Vote as VoteMapper;
use Photo\Service\AclService;
use Photo\Service\Album as AlbumService;
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
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(TagMapper::class),
            $container->get(VoteMapper::class),
            $container->get(AlbumService::class),
        );
    }
}
