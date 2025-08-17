<?php

declare(strict_types=1);

namespace Application\Router\Factory;

use Laminas\Router\RouteStackInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

final class LanguageAwareTreeRouteStackFactory implements DelegatorFactoryInterface
{
    /**
     * @param string $name
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $name,
        callable $callback,
        ?array $options = null,
    ): RouteStackInterface {
        // We do not need to inject anything special into the container. The MvcTranslator is already handled by the
        // `LanguageAwareTreeRouteStack`'s `HttpRouterDelegatorFactory`.

        return $callback();
    }
}
