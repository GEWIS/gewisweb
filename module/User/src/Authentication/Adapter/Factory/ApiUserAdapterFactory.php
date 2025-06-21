<?php

declare(strict_types=1);

namespace User\Authentication\Adapter\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use User\Authentication\Adapter\ApiUserAdapter;
use User\Mapper\ApiUser as ApiUserMapper;

class ApiUserAdapterFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiUserAdapter {
        return new ApiUserAdapter(
            $container->get(ApiUserMapper::class),
        );
    }
}
