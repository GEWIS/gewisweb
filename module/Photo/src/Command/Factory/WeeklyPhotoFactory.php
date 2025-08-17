<?php

declare(strict_types=1);

namespace Photo\Command\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Photo\Command\WeeklyPhoto;
use Photo\Service\Photo as PhotoService;
use Psr\Container\ContainerInterface;

class WeeklyPhotoFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): WeeklyPhoto {
        return new WeeklyPhoto($container->get(PhotoService::class));
    }
}
