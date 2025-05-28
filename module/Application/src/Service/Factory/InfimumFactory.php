<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\Infimum as InfimumService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class InfimumFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): InfimumService {
        return new InfimumService(
            $container->get('application_cache_infimum'),
            $container->get(MvcTranslator::class),
            $container->get('config')['infimum'],
        );
    }
}
