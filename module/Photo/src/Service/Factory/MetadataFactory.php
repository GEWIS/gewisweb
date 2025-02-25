<?php

declare(strict_types=1);

namespace Photo\Service\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Service\Metadata as MetadataService;
use Psr\Container\ContainerInterface;

class MetadataFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MetadataService {
        return new MetadataService();
    }
}
