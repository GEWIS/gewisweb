<?php

declare(strict_types=1);

namespace Application\Mapper\Factory;

use Application\Mapper\BaseMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class BaseMapperFactory implements FactoryInterface
{
    /**
     * @template T of BaseMapper
     *
     * @psalm-param class-string<T> $requestedName
     *
     * @return T
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ) {
        return new $requestedName($container->get('doctrine.entitymanager.orm_default'));
    }
}
