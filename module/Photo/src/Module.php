<?php

declare(strict_types=1);

namespace Photo;

use Application\Form\Factory\BaseFormFactory;
use Application\Mapper\Factory\BaseMapperFactory;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Doctrine\ORM\Events;
use Laminas\Mvc\MvcEvent;
use Photo\Command\Factory\WeeklyPhotoFactory as WeeklyPhotoCommandFactory;
use Photo\Command\WeeklyPhoto as WeeklyPhotoCommand;
use Photo\Form\Album as AlbumForm;
use Photo\Form\Factory\AlbumFactory as AlbumFormFactory;
use Photo\Form\SearchAlbum as SearchAlbumForm;
use Photo\Listener\AlbumDate as AlbumDateListener;
use Photo\Listener\Remove as RemoveListener;
use Photo\Mapper\Album as AlbumMapper;
use Photo\Mapper\Photo as PhotoMapper;
use Photo\Mapper\ProfilePhoto as ProfilePhotoMapper;
use Photo\Mapper\Tag as TagMapper;
use Photo\Mapper\Vote as VoteMapper;
use Photo\Mapper\WeeklyPhoto as WeeklyPhotoMapper;
use Photo\Service\AclService;
use Photo\Service\Admin as AdminService;
use Photo\Service\Album as AlbumService;
use Photo\Service\AlbumCover as AlbumCoverService;
use Photo\Service\Factory\AdminFactory as AdminServiceFactory;
use Photo\Service\Factory\AlbumCoverFactory as AlbumCoverServiceFactory;
use Photo\Service\Factory\AlbumFactory as AlbumServiceFactory;
use Photo\Service\Factory\MetadataFactory as MetadataServiceFactory;
use Photo\Service\Factory\PhotoFactory as PhotoServiceFactory;
use Photo\Service\Metadata as MetadataService;
use Photo\Service\Photo as PhotoService;
use Psr\Container\ContainerInterface;
use User\Authorization\AclServiceFactory;

class Module
{
    public function onBootstrap(MvcEvent $e): void
    {
        $container = $e->getApplication()->getServiceManager();
        $em = $container->get('doctrine.entitymanager.orm_default');
        $dem = $em->getEventManager();
        $dem->addEventListener([Events::prePersist], new AlbumDateListener());
        $photoService = $container->get(PhotoService::class);
        $albumService = $container->get(AlbumService::class);
        $dem->addEventListener([Events::preRemove], new RemoveListener($photoService, $albumService));
    }

    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig(): array
    {
        return [
            'factories' => [
                // Services
                AclService::class => AclServiceFactory::class,
                AdminService::class => AdminServiceFactory::class,
                AlbumCoverService::class => AlbumCoverServiceFactory::class,
                AlbumService::class => AlbumServiceFactory::class,
                MetadataService::class => MetadataServiceFactory::class,
                PhotoService::class => PhotoServiceFactory::class,
                // Mappers
                AlbumMapper::class => BaseMapperFactory::class,
                PhotoMapper::class => BaseMapperFactory::class,
                ProfilePhotoMapper::class => BaseMapperFactory::class,
                TagMapper::class => BaseMapperFactory::class,
                VoteMapper::class => BaseMapperFactory::class,
                WeeklyPhotoMapper::class => BaseMapperFactory::class,
                // Forms
                AlbumForm::class => AlbumFormFactory::class,
                SearchAlbumForm::class => BaseFormFactory::class,
                'photo_hydrator' => static function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                // Commands
                WeeklyPhotoCommand::class => WeeklyPhotoCommandFactory::class,
            ],
        ];
    }
}
