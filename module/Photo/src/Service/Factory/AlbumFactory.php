<?php

declare(strict_types=1);

namespace Photo\Service\Factory;

use Application\Service\FileStorage as FileStorageService;
use Decision\Service\Member as MemberService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Photo\Form\Album as AlbumForm;
use Photo\Form\SearchAlbum as SearchAlbumForm;
use Photo\Mapper\Album as AlbumMapper;
use Photo\Mapper\Tag as TagMapper;
use Photo\Mapper\WeeklyPhoto as WeeklyPhotoMapper;
use Photo\Service\AclService;
use Photo\Service\Album as AlbumService;
use Photo\Service\AlbumCover as AlbumCoverService;
use Photo\Service\Photo as PhotoService;
use Psr\Container\ContainerInterface;

class AlbumFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AlbumService {
        return new AlbumService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(PhotoService::class),
            $container->get(AlbumCoverService::class),
            $container->get(MemberService::class),
            $container->get(FileStorageService::class),
            $container->get(AlbumMapper::class),
            $container->get(TagMapper::class),
            $container->get(WeeklyPhotoMapper::class),
            $container->get(AlbumForm::class),
            $container->get(SearchAlbumForm::class),
        );
    }
}
