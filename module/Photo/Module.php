<?php

namespace Photo;

use Zend\Mvc\MvcEvent;
use Photo\Listener\AlbumDate as AlbumDateListener;
use Photo\Listener\Remove as RemoveListener;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $sm = $e->getApplication()->getServiceManager();
        $em = $sm->get('photo_doctrine_em');
        $dem = $em->getEventManager();
        $dem->addEventListener([\Doctrine\ORM\Events::prePersist], new AlbumDateListener());
        $dem->addEventListener([\Doctrine\ORM\Events::preRemove], new RemoveListener($sm));
    }

    /**
     * Get the autoloader configuration.
     *
     * @return array Autoloader config
     */
    public function getAutoloaderConfig()
    {
        if (APP_ENV === 'production') {
            return [
                'Zend\Loader\ClassMapAutoloader' => [
                __DIR__ . '/autoload_classmap.php',
                ]
            ];
        }

        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ]
            ]
        ];
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
            'invokables' => [
                'photo_service_album' => 'Photo\Service\Album',
                'photo_service_metadata' => 'Photo\Service\Metadata',
                'photo_service_photo' => 'Photo\Service\Photo',
                'photo_service_album_cover' => 'Photo\Service\AlbumCover',
                'photo_service_admin' => 'Photo\Service\Admin'
            ],
            'factories' => [
                'photo_form_album_edit' => function ($sm) {
                    $form = new Form\EditAlbum(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('photo_hydrator_album'));

                    return $form;
                },
                'photo_form_album_create' => function ($sm) {
                    $form = new Form\CreateAlbum(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('photo_hydrator_album'));

                    return $form;
                },
                'photo_hydrator_album' => function ($sm) {
                    return new \DoctrineModule\Stdlib\Hydrator\DoctrineObject(
                        $sm->get('photo_doctrine_em'), 'Photo\Model\Album'
                    );
                },
                'photo_mapper_album' => function ($sm) {
                    return new Mapper\Album(
                        $sm->get('photo_doctrine_em')
                    );
                },
                'photo_mapper_photo' => function ($sm) {
                    return new Mapper\Photo(
                        $sm->get('photo_doctrine_em')
                    );
                },
                'photo_mapper_tag' => function ($sm) {
                    return new Mapper\Tag(
                        $sm->get('photo_doctrine_em')
                    );
                },
                'photo_mapper_hit' => function ($sm) {
                    return new Mapper\Hit(
                        $sm->get('photo_doctrine_em')
                    );
                },
                'photo_mapper_weekly_photo' => function ($sm) {
                    return new Mapper\WeeklyPhoto(
                        $sm->get('photo_doctrine_em')
                    );
                },
                'photo_acl' => function ($sm) {
                    $acl = $sm->get('acl');

                    // add resources for this module
                    $acl->addResource('photo');
                    $acl->addResource('album');
                    $acl->addResource('tag');

                    // Only users are allowed to view photos and albums
                    $acl->allow('user', 'photo', 'view');
                    $acl->allow('user', 'album', 'view');

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
                'photo_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            ]
        ];
    }

}
