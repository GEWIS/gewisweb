<?php

declare(strict_types=1);

namespace Photo\Service\Factory;

use Application\Service\FileStorage as FileStorageService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Photo\Mapper\Photo as PhotoMapper;
use Photo\Service\AlbumCover as AlbumCoverService;
use Psr\Container\ContainerInterface;

class AlbumCoverFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AlbumCoverService {
        return new AlbumCoverService(
            $container->get(PhotoMapper::class),
            $container->get(FileStorageService::class),
            $container->get('config')['photo'],
            $container->get('config')['storage'],
        );
    }
}
