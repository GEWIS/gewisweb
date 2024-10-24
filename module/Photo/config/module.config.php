<?php

declare(strict_types=1);

namespace Photo;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Photo\Command\WeeklyPhoto;
use Photo\Controller\AlbumAdminController;
use Photo\Controller\AlbumController;
use Photo\Controller\ApiController;
use Photo\Controller\Factory\AlbumAdminControllerFactory;
use Photo\Controller\Factory\AlbumControllerFactory;
use Photo\Controller\Factory\ApiControllerFactory;
use Photo\Controller\Factory\PhotoAdminControllerFactory;
use Photo\Controller\Factory\PhotoControllerFactory;
use Photo\Controller\Factory\TagControllerFactory;
use Photo\Controller\PhotoAdminController;
use Photo\Controller\PhotoController;
use Photo\Controller\TagController;
use User\Listener\Authentication;

return [
    'router' => [
        'routes' => [
            'photo' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/photo',
                    'defaults' => [
                        'controller' => PhotoController::class,
                        'action' => 'index',
                        'auth_type' => Authentication::AUTH_USER,
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'album' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:album_type/:album_id[#pid=:photo_id]',
                            'constraints' => [
                                'album_id' => '[0-9]+',
                                'album_type' => '(album|member|weekly)',
                                'photo_id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'controller' => AlbumController::class,
                                'action' => 'index',
                            ],
                        ],
                    ],
                    'photo' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/photo/:photo_id',
                            'constraints' => [
                                'photo_id' => '[0-9]+',
                            ],
                        ],
                        'may_terminate' => false,
                        'child_routes' => [
                            'tag' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/tag/:lidnr',
                                    'constraints' => [
                                        'lidnr' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'controller' => TagController::class,
                                    ],
                                ],
                                'may_terminate' => false,
                                'child_routes' => [
                                    'add' => [
                                        'type' => Literal::class,
                                        'options' => [
                                            'route' => '/add',
                                            'defaults' => [
                                                'action' => 'add',
                                            ],
                                        ],
                                    ],
                                    'remove' => [
                                        'type' => Literal::class,
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
                                'type' => Literal::class,
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
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/download/:photo_id',
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
                        'type' => Segment::class,
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
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/weekly',
                            'defaults' => [
                                'action' => 'weekly',
                            ],
                        ],
                    ],
                    'set_profile_photo' => [
                        'type' => Segment::class,
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
                        'type' => Segment::class,
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
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/photo',
                    'defaults' => [
                        'controller' => AlbumAdminController::class,
                        'action' => 'index',
                        'auth_type' => Authentication::AUTH_USER,
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'album' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/album/:album_id',
                            'defaults' => [
                                'action' => 'view',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'album_year' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/albums/:year',
                            'defaults' => [
                                'action' => 'year',
                            ],
                            'constraints' => [
                                'year' => '[0-9]+',
                            ],
                        ],
                    ],
                    'album_undated' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/albums/undated',
                            'defaults' => [
                                'action' => 'undated',
                            ],
                        ],
                    ],
                    'album_edit' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/album/:album_id/edit',
                            'defaults' => [
                                'action' => 'edit',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'album_create' => [
                        'type' => Segment::class,
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
                        'type' => Segment::class,
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
                    'album_upload' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/album/:album_id/upload',
                            'defaults' => [
                                'action' => 'upload',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'album_move' => [
                        'type' => Segment::class,
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
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/album/:album_id/delete',
                            'defaults' => [
                                'action' => 'delete',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'album_cover' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/album/:album_id/cover',
                            'defaults' => [
                                'action' => 'cover',
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                            ],
                        ],
                    ],
                    'photo_move' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/photo/:photo_id/move',
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
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/photo/:photo_id/delete',
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
                'type' => Literal::class,
                'options' => [
                    'route' => '/api/photo',
                    'defaults' => [
                        'controller' => ApiController::class,
                        'action' => 'index',
                        'auth_type' => Authentication::AUTH_API,
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'album_list' => [
                        'type' => Segment::class,
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
                    'details' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:photo_id/details',
                            'defaults' => [
                                'action' => 'details',
                            ],
                            'constraints' => [
                                'photo_id' => '[0-9]+',
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
    'view_manager' => [
        'template_path_stack' => [
            'photo' => __DIR__ . '/../view/',
        ],
        'template_map' => [
            'photo/album-admin/create' => __DIR__ . '/../view/photo/album-admin/edit.phtml',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'doctrine' => [
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => AttributeDriver::class,
                'paths' => [
                    __DIR__ . '/../src/Model/',
                ],
            ],
            'orm_default' => [
                'drivers' => [
                    __NAMESPACE__ . '\Model' => __NAMESPACE__ . '_driver',
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
