<?php

declare(strict_types=1);

namespace Decision\Controller\Factory;

use Decision\Controller\DecisionController;
use Decision\Controller\FileBrowser\LocalFileReader;
use Decision\Service\AclService;
use Decision\Service\Decision as DecisionService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class DecisionControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): DecisionController {
        return new DecisionController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(DecisionService::class),
            $container->get(LocalFileReader::class),
        );
    }
}
