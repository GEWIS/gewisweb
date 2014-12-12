<?php

return array(
    'router' => array(
        'routes' => array(
            'photo' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/photo',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Photo\Controller',
                        'controller' => 'Photo',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'album' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/album[/:album_id]',
                            'constraints' => array(
                                'album_id' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'Album',
                                'action' => 'index',
                            ),
                        ),
                    ),
                    'photo' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/photo[/:photo_id]',
                            'constraints' => array(
                                'photo_id' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'Photo',
                                'action' => 'view',
                            ),
                        ),
                    ),
                ),
            ), 'admin_photo' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/admin/photo',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Photo\Controller',
                        'controller' => 'Admin',
                        'action' => 'index'
                    )
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/index',
                        ),
                    ),
                    'album' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/album/:id',
                            'defaults' => array(
                                'action' => 'viewAlbum'
                            ),
                            'constraints' => array(
                                'id' => '[0-9]+',
                            ),
                        ),
                    ),
                    'albumaction' => array(
                        'type' => 'literal',
                        'options' => array(
                            'route' => '/album',
                            'defaults' => array(
                                'action' => 'album'
                            ),
                        ),
                    ),
                    'albumaction' => array(
                        'type' => 'literal',
                        'options' => array(
                            'route' => '/album/create',
                            'defaults' => array(
                                'action' => 'createAlbum'
                            ),
                        ),
                    ),
                ),
            )
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Photo\Controller\Photo' => 'Photo\Controller\PhotoController',
            'Photo\Controller\Album' => 'Photo\Controller\AlbumController',
            'Photo\Controller\Admin' => 'Photo\Controller\AdminController'
        )
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'photo' => __DIR__ . '/../view/'
        )
    ),
    'doctrine' => array(
        'driver' => array(
            'photo_entities' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/Photo/Model/')
            ),
            'orm_default' => array(
                'drivers' => array(
                    'Photo\Model' => 'photo_entities'
                )
            )
        )
    )
);
