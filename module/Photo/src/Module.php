<?php

declare(strict_types=1);

namespace Photo;

use Doctrine\Laminas\Hydrator\DoctrineObject;
use Doctrine\ORM\Events;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Mvc\MvcEvent;
use Photo\Command\WeeklyPhoto;
use Photo\Form\Album as AlbumForm;
use Photo\Listener\AlbumDate as AlbumDateListener;
use Photo\Listener\Remove as RemoveListener;
use Photo\Mapper\Album as AlbumMapper;
use Photo\Mapper\Photo as PhotoMapper;
use Photo\Mapper\ProfilePhoto as ProfilePhotoMapper;
use Photo\Mapper\Tag as TagMapper;
use Photo\Mapper\Vote as VoteMapper;
use Photo\Mapper\WeeklyPhoto as WeeklyPhotoMapper;
use Photo\Service\Admin as AdminService;
use Photo\Service\Album as AlbumService;
use Photo\Service\AlbumCover as AlbumCoverService;
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
        $photoService = $container->get('photo_service_photo');
        $albumService = $container->get('photo_service_album');
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
                'photo_service_album' => static function (ContainerInterface $container) {
                    $aclService = $container->get('photo_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $photoService = $container->get('photo_service_photo');
                    $albumCoverService = $container->get('photo_service_album_cover');
                    $memberService = $container->get('decision_service_member');
                    $storageService = $container->get('application_service_storage');
                    $albumMapper = $container->get('photo_mapper_album');
                    $tagMapper = $container->get('photo_mapper_tag');
                    $weeklyPhotoMapper = $container->get('photo_mapper_weekly_photo');
                    $albumForm = $container->get('photo_form_album');

                    return new AlbumService(
                        $aclService,
                        $translator,
                        $photoService,
                        $albumCoverService,
                        $memberService,
                        $storageService,
                        $albumMapper,
                        $tagMapper,
                        $weeklyPhotoMapper,
                        $albumForm,
                    );
                },
                'photo_service_metadata' => static function () {
                    return new MetadataService();
                },
                'photo_service_photo' => static function (ContainerInterface $container) {
                    $aclService = $container->get('photo_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $memberService = $container->get('decision_service_member');
                    $storageService = $container->get('application_service_storage');
                    $photoMapper = $container->get('photo_mapper_photo');
                    $tagMapper = $container->get('photo_mapper_tag');
                    $voteMapper = $container->get('photo_mapper_vote');
                    $weeklyPhotoMapper = $container->get('photo_mapper_weekly_photo');
                    $profilePhotoMapper = $container->get('photo_mapper_profile_photo');
                    $photoConfig = $container->get('config')['photo'];

                    return new PhotoService(
                        $aclService,
                        $translator,
                        $memberService,
                        $storageService,
                        $photoMapper,
                        $tagMapper,
                        $voteMapper,
                        $weeklyPhotoMapper,
                        $profilePhotoMapper,
                        $photoConfig,
                    );
                },
                'photo_service_album_cover' => static function (ContainerInterface $container) {
                    $photoMapper = $container->get('photo_mapper_photo');
                    $storage = $container->get('application_service_storage');
                    $photoConfig = $container->get('config')['photo'];
                    $storageConfig = $container->get('config')['storage'];

                    return new AlbumCoverService(
                        $photoMapper,
                        $storage,
                        $photoConfig,
                        $storageConfig,
                    );
                },
                'photo_service_admin' => static function (ContainerInterface $container) {
                    $aclService = $container->get('photo_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $photoService = $container->get('photo_service_photo');
                    $metadataService = $container->get('photo_service_metadata');
                    $storageService = $container->get('application_service_storage');
                    $photoMapper = $container->get('photo_mapper_photo');

                    return new AdminService(
                        $aclService,
                        $translator,
                        $photoService,
                        $metadataService,
                        $storageService,
                        $photoMapper,
                    );
                },
                'photo_form_album' => static function (ContainerInterface $container) {
                    $form = new AlbumForm(
                        $container->get(MvcTranslator::class),
                    );
                    $form->setHydrator($container->get('photo_hydrator'));

                    return $form;
                },
                'photo_hydrator' => static function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'photo_mapper_album' => static function (ContainerInterface $container) {
                    return new AlbumMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'photo_mapper_photo' => static function (ContainerInterface $container) {
                    return new PhotoMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'photo_mapper_profile_photo' => static function (ContainerInterface $container) {
                    return new ProfilePhotoMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'photo_mapper_tag' => static function (ContainerInterface $container) {
                    return new TagMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'photo_mapper_weekly_photo' => static function (ContainerInterface $container) {
                    return new WeeklyPhotoMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'photo_mapper_vote' => static function (ContainerInterface $container) {
                    return new VoteMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'photo_service_acl' => AclServiceFactory::class,
                WeeklyPhoto::class => static function (ContainerInterface $container) {
                    $weeklyPhoto = new WeeklyPhoto();
                    $photoService = $container->get('photo_service_photo');
                    $weeklyPhoto->setPhotoService($photoService);

                    return $weeklyPhoto;
                },
            ],
        ];
    }
}
