<?php

declare(strict_types=1);

namespace Photo;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Doctrine\ORM\Events;
use Exception;
use Laminas\Mvc\MvcEvent;
use Psr\Container\ContainerInterface;
use League\Glide\Urls\UrlBuilderFactory;
use Photo\Command\WeeklyPhoto;
use Photo\Form\{
    CreateAlbum as CreateAlbumForm,
    EditAlbum as EditAlbumForm,
};
use Photo\Listener\{
    AlbumDate as AlbumDateListener,
    Remove as RemoveListener,
};
use Photo\Mapper\{
    Album as AlbumMapper,
    Photo as PhotoMapper,
    ProfilePhoto as ProfilePhotoMapper,
    Tag as TagMapper,
    Vote as VoteMapper,
    WeeklyPhoto as WeeklyPhotoMapper,
};
use Photo\Service\{
    Admin as AdminService,
    Album as AlbumService,
    AlbumCover as AlbumCoverService,
    Metadata as MetadataService,
    Photo as PhotoService,
};
use Photo\View\Helper\GlideUrl;
use User\Authorization\AclServiceFactory;

class Module
{
    /**
     * @param MvcEvent $e
     */
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
                'photo_service_album' => function (ContainerInterface $container) {
                    $aclService = $container->get('photo_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $photoService = $container->get('photo_service_photo');
                    $albumCoverService = $container->get('photo_service_album_cover');
                    $memberService = $container->get('decision_service_member');
                    $storageService = $container->get('application_service_storage');
                    $albumMapper = $container->get('photo_mapper_album');
                    $tagMapper = $container->get('photo_mapper_tag');
                    $weeklyPhotoMapper = $container->get('photo_mapper_weekly_photo');
                    $createAlbumForm = $container->get('photo_form_album_create');
                    $editAlbumForm = $container->get('photo_form_album_edit');

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
                        $createAlbumForm,
                        $editAlbumForm,
                    );
                },
                'photo_service_metadata' => function () {
                    return new MetadataService();
                },
                'photo_service_photo' => function (ContainerInterface $container) {
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
                'photo_service_album_cover' => function (ContainerInterface $container) {
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
                'photo_service_admin' => function (ContainerInterface $container) {
                    $aclService = $container->get('photo_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $photoService = $container->get('photo_service_photo');
                    $metadataService = $container->get('photo_service_metadata');
                    $storageService = $container->get('application_service_storage');
                    $photoMapper = $container->get('photo_mapper_photo');
                    $photoConfig = $container->get('config')['photo'];

                    return new AdminService(
                        $aclService,
                        $translator,
                        $photoService,
                        $metadataService,
                        $storageService,
                        $photoMapper,
                        $photoConfig,
                    );
                },
                'photo_form_album_edit' => function (ContainerInterface $container) {
                    $form = new EditAlbumForm(
                        $container->get(MvcTranslator::class)
                    );
                    $form->setHydrator($container->get('photo_hydrator'));

                    return $form;
                },
                'photo_form_album_create' => function (ContainerInterface $container) {
                    $form = new CreateAlbumForm(
                        $container->get(MvcTranslator::class)
                    );
                    $form->setHydrator($container->get('photo_hydrator'));

                    return $form;
                },
                'photo_hydrator' => function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_album' => function (ContainerInterface $container) {
                    return new AlbumMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_photo' => function (ContainerInterface $container) {
                    return new PhotoMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_profile_photo' => function (ContainerInterface $container) {
                    return new ProfilePhotoMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_tag' => function (ContainerInterface $container) {
                    return new TagMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_weekly_photo' => function (ContainerInterface $container) {
                    return new WeeklyPhotoMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_vote' => function (ContainerInterface $container) {
                    return new VoteMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_service_acl' => AclServiceFactory::class,
                WeeklyPhoto::class => function (ContainerInterface $container) {
                    $weeklyPhoto = new WeeklyPhoto();
                    $photoService = $container->get('photo_service_photo');
                    $weeklyPhoto->setPhotoService($photoService);
                    return $weeklyPhoto;
                },
            ],
        ];
    }

    /**
     * @return array
     */
    public function getViewHelperConfig(): array
    {
        return [
            'factories' => [
                'glideUrl' => function (ContainerInterface $container) {
                    $helper = new GlideUrl();
                    $config = $container->get('config');
                    if (
                        !isset($config['glide']) || !isset($config['glide']['base_url'])
                        || !isset($config['glide']['signing_key'])
                    ) {
                        throw new Exception('Invalid glide configuration');
                    }

                    $urlBuilder = UrlBuilderFactory::create(
                        $config['glide']['base_url'],
                        $config['glide']['signing_key']
                    );
                    $helper->setUrlBuilder($urlBuilder);

                    return $helper;
                },
            ],
        ];
    }
}
