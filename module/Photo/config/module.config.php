<?php

use Photo\Command\WeeklyPhoto;
use Photo\Controller\{
    AlbumAdminController,
    AlbumController,
    ApiController,
    PhotoAdminController,
    PhotoController,
    TagController,
    Plugin\AlbumPlugin,
};
use Photo\Controller\Factory\{
    AlbumAdminControllerFactory,
    AlbumControllerFactory,
    ApiControllerFactory,
    PhotoAdminControllerFactory,
    PhotoControllerFactory,
    TagControllerFactory,
    Plugin\AlbumPluginFactory,
};

return [
    'router' => [
        'routes' => [
            'photo' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/photo',
                    'defaults' => [
                        'controller' => PhotoController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'member' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/member/:lidnr[/:page]',
                            'constraints' => [
                                'lidnr' => '[0-9]+',
                                'page' => '[0-9]+',
                            ],
                            'defaults' => [
                                'controller' => AlbumController::class,
                                'action' => 'member',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'photo' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/photo/:photo_id',
                                    'constraints' => [
                                        'photo_id' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'controller' => PhotoController::class,
                                        'action' => 'member',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'album' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/album[/:album_id][/:page]',
                            'constraints' => [
                                'album_id' => '[0-9]+',
                                'page' => '[0-9]+',
                            ],
                            'defaults' => [
                                'controller' => AlbumController::class,
                                'action' => 'index',
                            ],
                        ],
                    ],
                    'album_beta' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/beta/:album_type/:album_id',
                            'constraints' => [
                                'album_id' => '[0-9]+',
                                'album_type' => '(album|member)',
                            ],
                            'defaults' => [
                                'controller' => AlbumController::class,
                                'action' => 'indexNew',
                            ],
                        ],
                    ],
                    'photo' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/view[/:photo_id]',
                            'constraints' => [
                                'photo_id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'view',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'tag' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/tag[/:lidnr]',
                                    'constraints' => [
                                        'lidnr' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'controller' => 'Tag',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'add' => [
                                        'type' => 'Literal',
                                        'options' => [
                                            'route' => '/add',
                                            'defaults' => [
                                                'action' => 'add',
                                            ],
                                        ],
                                    ],
                                    'remove' => [
                                        'type' => 'Literal',
                                        'options' => [
                                            'route' => '/remove',
                                            'defaults' => [
                                                'action' => 'remove',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'vote' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/vote',
                                    'defaults' => [
                                        'action' => 'vote',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'photo_download' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/download[/:photo_id]',
                            'constraints' => [
                                'photo_id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'download',
                            ],
                        ],
                    ],
                    // Route for categorizing albums by association year.
                    'year' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:year]',
                            'constraints' => [
                                'year' => '\d{4}',
                            ],
                            'defaults' => [
                                'action' => 'index',
                            ],
                        ],
                    ],
                    'weekly' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/weekly',
                            'defaults' => [
                                'action' => 'weekly',
                            ],
                        ],
                    ],
                    'set_profile_photo' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/setprofilephoto/:photo_id',
                            'constraints' => [
                                'photo_id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'setProfilePhoto',
                            ],
                        ],
                    ],
                    'remove_profile_photo' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/removeprofilephoto[/:photo_id]',
                            'constraints' => [
                                'photo_id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'removeProfilePhoto',
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'admin_photo' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/admin/photo',
                    'defaults' => [
                        'controller' => AlbumAdminController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/index',
                        ],
                    ],
                    'album' => [
                        'type' => 'literal',
                        'options' => [
                            'route' => '/album',
                            'defaults' => [
                                'action' => 'index',
                            ],
                        ],
                    ],
                    'album_index' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/album[/:album_id]',
                            'defaults' => [
                                'action' => 'page',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'album_page' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/album[/:album_id][/:page]',
                            'defaults' => [
                                'action' => 'page',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                                'page' => '[0-9]+',
                            ],
                        ],
                    ],
                    'album_edit' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/album[/:album_id]/edit',
                            'defaults' => [
                                'action' => 'edit',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'album_create' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/album[/:album_id]/create',
                            'defaults' => [
                                'action' => 'create',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]*',
                            ],
                        ],
                    ],
                    'album_add' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/album[/:album_id]/add',
                            'defaults' => [
                                'action' => 'add',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'album_import' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/album[/:album_id]/import',
                            'defaults' => [
                                'action' => 'import',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'album_upload' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/album[/:album_id]/upload',
                            'defaults' => [
                                'action' => 'upload',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'album_move' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/album[/:album_id]/move',
                            'defaults' => [
                                'action' => 'move',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'album_delete' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/album[/:album_id]/delete',
                            'defaults' => [
                                'action' => 'delete',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'album_cover' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/album[/:album_id]/cover',
                            'defaults' => [
                                'action' => 'cover',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'photo_index' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/photo[/:photo_id]',
                            'defaults' => [
                                'controller' => PhotoAdminController::class,
                                'action' => 'index',
                            ],
                            'constraints' => [
                                'photo_id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'photo_move' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/photo[/:photo_id]/move',
                            'defaults' => [
                                'controller' => PhotoAdminController::class,
                                'action' => 'move',
                            ],
                            'constraints' => [
                                'photo_id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'photo_delete' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/photo[/:photo_id]/delete',
                            'defaults' => [
                                'controller' => PhotoAdminController::class,
                                'action' => 'delete',
                            ],
                            'constraints' => [
                                'photo_id' => '[0-9]+',
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'api_photo' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/api/photo',
                    'defaults' => [
                        'controller' => ApiController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'album_list' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/album/:album_id',
                            'defaults' => [
                                'action' => 'list',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            AlbumAdminController::class => AlbumAdminControllerFactory::class,
            AlbumController::class => AlbumControllerFactory::class,
            ApiController::class => ApiControllerFactory::class,
            PhotoAdminController::class => PhotoAdminControllerFactory::class,
            PhotoController::class => PhotoControllerFactory::class,
            TagController::class => TagControllerFactory::class,
        ],
    ],
    'controller_plugins' => [
        'aliases' => [
            'AlbumPlugin' => AlbumPlugin::class,
        ],
        'factories' => [
            AlbumPlugin::class => AlbumPluginFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'photo' => __DIR__ . '/../view/',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'doctrine' => [
        'driver' => [
            'photo_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Model/'],
            ],
            'orm_default' => [
                'drivers' => [
                    'Photo\Model' => 'photo_entities',
                ],
            ],
        ],
    ],
    'laminas-cli' => [
        'commands' => [
            'photo:weeklyphoto' => WeeklyPhoto::class,
        ],
    ],
];
