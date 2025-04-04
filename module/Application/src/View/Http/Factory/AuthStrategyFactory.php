<?php

declare(strict_types=1);

namespace Application\View\Http\Factory;

use Application\View\Http\AuthStrategy;
use Laminas\Mvc\Service\HttpViewManagerConfigTrait;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AuthStrategyFactory implements FactoryInterface
{
    use HttpViewManagerConfigTrait;

    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AuthStrategy {
        $config = $this->getConfig($container);
        $unauthenticatedTemplate = $config['unauthenticated_template'] ?? $config['exception_template'] ?? 'error';
        $unauthorizedTemplate = $config['unauthorized_template'] ?? $config['exception_template'] ?? 'error';

        return new AuthStrategy(
            $unauthenticatedTemplate,
            $unauthorizedTemplate,
        );
    }
}
