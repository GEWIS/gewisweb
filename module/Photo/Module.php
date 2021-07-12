<?php

namespace Photo;

use Doctrine\ORM\Events;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Exception;
use League\Glide\Urls\UrlBuilderFactory;
use Photo\Listener\AlbumDate as AlbumDateListener;
use Photo\Listener\Remove as RemoveListener;
use Photo\Service\Admin;
use Photo\Service\Album;
use Photo\Service\AlbumCover;
use Photo\Service\Metadata;
use Photo\Service\Photo;
use Photo\View\Helper\GlideUrl;
use Zend\Cache\StorageFactory;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $sm = $e->getApplication()->getServiceManager();
        $em = $sm->get('photo_doctrine_em');
        $dem = $em->getEventManager();
        $dem->addEventListener([Events::prePersist], new AlbumDateListener());
        $photoService = $sm->get('photo_service_photo');
        $albumService = $sm->get('photo_service_album');
        $dem->addEventListener([Events::preRemove], new RemoveListener($photoService, $albumService));
    }

    /**
     * Get the autoloader configuration.
     */
    public function getAutoloaderConfig()
    {
    }

    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'photo_service_album' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('photo_acl');
                    $photoService = $sm->get('photo_service_photo');
                    $albumCoverService = $sm->get('photo_service_album_cover');
                    $memberService = $sm->get('decision_service_member');
                    $storageService = $sm->get('application_service_storage');
                    $albumMapper = $sm->get('photo_mapper_album');
                    $createAlbumForm = $sm->get('photo_form_album_create');
                    $editAlbumForm = $sm->get('photo_form_album_edit');
                    return new Album(
                        $translator,
                        $userRole,
                        $acl,
                        $photoService,
                        $albumCoverService,
                        $memberService,
                        $storageService,
                        $albumMapper,
                        $createAlbumForm,
                        $editAlbumForm
                    );
                },
                'photo_service_metadata' => function () {
                    return new Metadata();
                },
                'photo_service_photo' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('photo_acl');
                    $memberService = $sm->get('decision_service_member');
                    $storageService = $sm->get('application_service_storage');
                    $photoMapper = $sm->get('photo_mapper_photo');
                    $albumMapper = $sm->get('photo_mapper_album');
                    $tagMapper = $sm->get('photo_mapper_tag');
                    $hitMapper = $sm->get('photo_mapper_hit');
                    $voteMapper = $sm->get('photo_mapper_vote');
                    $weeklyPhotoMapper = $sm->get('photo_mapper_weekly_photo');
                    $profilePhotoMapper = $sm->get('photo_mapper_profile_photo');
                    $photoConfig = $sm->get('config')['photo'];
                    return new Photo(
                        $translator,
                        $userRole,
                        $acl,
                        $memberService,
                        $storageService,
                        $photoMapper,
                        $albumMapper,
                        $tagMapper,
                        $hitMapper,
                        $voteMapper,
                        $weeklyPhotoMapper,
                        $profilePhotoMapper,
                        $photoConfig
                    );
                },
                'photo_service_album_cover' => function (ServiceLocatorInterface $sm) {
                    $photoMapper = $sm->get('photo_mapper_photo');
                    $albumMapper = $sm->get('photo_mapper_album');
                    $storage = $sm->get('application_service_storage');
                    $photoConfig = $sm->get('config')['photo'];
                    $storageConfig = $sm->get('config')['storage'];
                    return new AlbumCover($photoMapper, $albumMapper, $storage, $photoConfig, $storageConfig);
                },
                'photo_service_admin' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('photo_acl');
                    $photoService = $sm->get('photo_service_photo');
                    $albumService = $sm->get('photo_service_album');
                    $metadataService = $sm->get('photo_service_metadata');
                    $storageService = $sm->get('application_service_storage');
                    $photoMapper = $sm->get('photo_mapper_photo');
                    $photoConfig = $sm->get('config')['photo'];
                    return new Admin(
                        $translator,
                        $userRole,
                        $acl,
                        $photoService,
                        $albumService,
                        $metadataService,
                        $storageService,
                        $photoMapper,
                        $photoConfig
                    );
                },
                'photo_form_album_edit' => function (ServiceLocatorInterface $sm) {
                    $form = new Form\EditAlbum(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('photo_hydrator_album'));

                    return $form;
                },
                'photo_form_album_create' => function (ServiceLocatorInterface $sm) {
                    $form = new Form\CreateAlbum(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('photo_hydrator_album'));

                    return $form;
                },
                'photo_hydrator_album' => function (ServiceLocatorInterface $sm) {
                    return new DoctrineObject(
                        $sm->get('photo_doctrine_em'),
                        'Photo\Model\Album'
                    );
                },
                'photo_mapper_album' => function (ServiceLocatorInterface $sm) {
                    return new Mapper\Album(
                        $sm->get('photo_doctrine_em')
                    );
                },
                'photo_mapper_photo' => function (ServiceLocatorInterface $sm) {
                    return new Mapper\Photo(
                        $sm->get('photo_doctrine_em')
                    );
                },
                'photo_mapper_profile_photo' => function (ServiceLocatorInterface $sm) {
                    return new Mapper\ProfilePhoto(
                        $sm->get('photo_doctrine_em')
                    );
                },
                'photo_mapper_tag' => function (ServiceLocatorInterface $sm) {
                    return new Mapper\Tag(
                        $sm->get('photo_doctrine_em')
                    );
                },
                'photo_mapper_hit' => function (ServiceLocatorInterface $sm) {
                    return new Mapper\Hit(
                        $sm->get('photo_doctrine_em')
                    );
                },
                'photo_mapper_weekly_photo' => function (ServiceLocatorInterface $sm) {
                    return new Mapper\WeeklyPhoto(
                        $sm->get('photo_doctrine_em')
                    );
                },
                'photo_mapper_vote' => function (ServiceLocatorInterface $sm) {
                    return new Mapper\Vote(
                        $sm->get('photo_doctrine_em')
                    );
                },
                'photo_acl' => function (ServiceLocatorInterface $sm) {
                    $acl = $sm->get('acl');

                    // add resources for this module
                    $acl->addResource('photo');
                    $acl->addResource('album');
                    $acl->addResource('tag');

                    // Only users and 'the screen' are allowed to view photos and albums
                    $acl->allow('user', 'photo', 'view');
                    $acl->allow('user', 'album', 'view');

                    $acl->allow('apiuser', 'photo', 'view');
                    $acl->allow('apiuser', 'album', 'view');

                    // Users are allowed to view, remove and add tags
                    $acl->allow('user', 'tag', ['view', 'add', 'remove']);

                    // Users are allowed to download photos
                    $acl->allow('user', 'photo', ['download', 'view_metadata']);

                    $acl->allow('photo_guest', 'photo', 'view');
                    $acl->allow('photo_guest', 'album', 'view');
                    $acl->allow('photo_guest', 'photo', ['download', 'view_metadata']);

                    return $acl;
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                // reused code from the eduction module
                'photo_doctrine_em' => function (ServiceLocatorInterface $sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                },
                'album_page_cache' => function () {
                    return StorageFactory::factory(
                        array(
                            'adapter' => array(
                                'name' => 'filesystem',
                                'options' => array(
                                    'dirLevel' => 2,
                                    'cacheDir' => 'data/cache',
                                    'dirPermission' => 0755,
                                    'filePermission' => 0666,
                                    'namespaceSeparator' => '-db-'
                                ),
                            ),
                            'plugins' => array('serializer'),
                        )
                    );
                },
            ]
        ];
    }

    public function getViewHelperConfig()
    {
        return [
            'factories' => [
                'glideUrl' => function (ServiceLocatorInterface $sm) {
                    $helper = new GlideUrl();
                    $config = $sm->get('config');
                    if (!isset($config['glide']) || !isset($config['glide']['base_url'])
                        || !isset($config['glide']['signing_key'])) {
                        throw new Exception('Invalid glide configuration');
                    }

                    $urlBuilder = UrlBuilderFactory::create(
                        $config['glide']['base_url'],
                        $config['glide']['signing_key']
                    );
                    $helper->setUrlBuilder($urlBuilder);
                    return $helper;
                },
            ]
        ];
    }
}
