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
                            'route' => '/album[/:album_id][/:page]',
                            'constraints' => array(
                                'album_id' => '[0-9]+',
                                'page' => '[0-9]+',
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
                            'route' => '/view[/:photo_id]',
                            'constraints' => array(
                                'photo_id' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'Photo',
                                'action' => 'view',
                            ),
                        ),
                    ),
                    'photo_download' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/download[/:photo_id]',
                            'constraints' => array(
                                'photo_id' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'Photo',
                                'action' => 'download',
                            ),
                        ),
                    ),
                    'tag' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/tag[/:photo_id]',
                            'constraints' => array(
                                'photo_id' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'Tag',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'add' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/add',
                                    'defaults' => array(
                                        'action' => 'add',
                                    ),
                                ),
                            ),
                            'remove' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/remove',
                                    'defaults' => array(
                                        'action' => 'remove',
                                    ),
                                ),
                            ),
                        ),
                    ),
                    // Route for categorizing albums by association year.
                    'year' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[/:year]',
                            'constraints' => array(
                                'year' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'Photo',
                                'action' => 'index',
                            ),
                        ),
                    ),
                ),
            ),
            'admin_photo' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/admin/photo',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Photo\Controller',
                        'controller' => 'AlbumAdmin',
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
                        'type' => 'literal',
                        'options' => array(
                            'route' => '/album',
                            'defaults' => array(
                                'controller' => 'AlbumAdmin',
                                'action' => 'index'
                            ),
                        ),
                    ),
                    'album_index' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/album[/:album_id]',
                            'defaults' => array(
                                'controller' => 'AlbumAdmin',
                                'action' => 'page'
                            ),
                            'constraints' => array(
                                'album_id' => '[0-9]+',
                            ),
                        ),
                    ),
                    'album_page' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/album[/:album_id][/:page]',
                            'defaults' => array(
                                'controller' => 'AlbumAdmin',
                                'action' => 'page'
                            ),
                            'constraints' => array(
                                'album_id' => '[0-9]+',
                                'page' => '[0-9]+',
                            ),
                        ),
                    ),
                    'album_edit' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/album[/:album_id]/edit',
                            'defaults' => array(
                                'controller' => 'AlbumAdmin',
                                'action' => 'edit'
                            ),
                            'constraints' => array(
                                'album_id' => '[0-9]+',
                            ),
                        ),
                    ),
                    'album_create' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/album[/:album_id]/create',
                            'defaults' => array(
                                'controller' => 'AlbumAdmin',
                                'action' => 'create'
                            ),
                            'constraints' => array(
                                'album_id' => '[0-9]*',
                            ),
                        ),
                    ),
                    'album_add' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/album[/:album_id]/add',
                            'defaults' => array(
                                'controller' => 'AlbumAdmin',
                                'action' => 'add'
                            ),
                            'constraints' => array(
                                'album_id' => '[0-9]+',
                            ),
                        ),
                    ),
                    'album_import' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/album[/:album_id]/import',
                            'defaults' => array(
                                'controller' => 'AlbumAdmin',
                                'action' => 'import'
                            ),
                            'constraints' => array(
                                'album_id' => '[0-9]+',
                            ),
                        ),
                    ),
                    'album_upload' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/album[/:album_id]/upload',
                            'defaults' => array(
                                'controller' => 'AlbumAdmin',
                                'action' => 'upload'
                            ),
                            'constraints' => array(
                                'album_id' => '[0-9]+',
                            ),
                        ),
                    ),
                    'album_move' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/album[/:album_id]/move',
                            'defaults' => array(
                                'controller' => 'AlbumAdmin',
                                'action' => 'move'
                            ),
                            'constraints' => array(
                                'album_id' => '[0-9]+',
                            ),
                        ),
                    ),
                    'album_delete' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/album[/:album_id]/delete',
                            'defaults' => array(
                                'controller' => 'AlbumAdmin',
                                'action' => 'delete'
                            ),
                            'constraints' => array(
                                'album_id' => '[0-9]+',
                            ),
                        ),
                    ),
                    'album_cover' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/album[/:album_id]/cover',
                            'defaults' => array(
                                'controller' => 'AlbumAdmin',
                                'action' => 'cover'
                            ),
                            'constraints' => array(
                                'album_id' => '[0-9]+',
                            ),
                        ),
                    ),
                    'photo_index' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/photo[/:photo_id]',
                            'defaults' => array(
                                'controller' => 'PhotoAdmin',
                                'action' => 'index'
                            ),
                            'constraints' => array(
                                'photo_id' => '[0-9]+',
                            ),
                        ),
                    ),
                    'photo_move' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/photo[/:photo_id]/move',
                            'defaults' => array(
                                'controller' => 'PhotoAdmin',
                                'action' => 'move'
                            ),
                            'constraints' => array(
                                'photo_id' => '[0-9]+',
                            ),
                        ),
                    ),
                    'photo_delete' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/photo[/:photo_id]/delete',
                            'defaults' => array(
                                'controller' => 'PhotoAdmin',
                                'action' => 'delete'
                            ),
                            'constraints' => array(
                                'photo_id' => '[0-9]+',
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
            'Photo\Controller\AlbumAdmin' => 'Photo\Controller\AlbumAdminController',
            'Photo\Controller\PhotoAdmin' => 'Photo\Controller\PhotoAdminController',
            'Photo\Controller\Tag' => 'Photo\Controller\TagController'
        )
    ),
    'controller_plugins' => array(
        'invokables' => array(
            'AlbumPlugin' => 'Photo\Controller\Plugin\AlbumPlugin',
        )
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'photo' => __DIR__ . '/../view/'
        ),
        'strategies' => array(
            'ViewJsonStrategy',
        ),
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
