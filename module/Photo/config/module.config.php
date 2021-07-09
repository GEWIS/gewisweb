<?php

use Interop\Container\ContainerInterface;
use Photo\Controller\AlbumAdminController;
use Photo\Controller\AlbumController;
use Photo\Controller\ApiController;
use Photo\Controller\PhotoAdminController;
use Photo\Controller\PhotoController;
use Photo\Controller\Plugin\AlbumPlugin;
use Photo\Controller\TagController;

return [
    'router' => [
        'routes' => [
            'photo' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/photo',
                    'defaults' => [
                        '__NAMESPACE__' => 'Photo\Controller',
                        'controller' => 'Photo',
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
                                'controller' => 'Album',
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
                                        'controller' => 'Photo',
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
                                'controller' => 'Album',
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
                                'controller' => 'Album',
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
                                'controller' => 'Photo',
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
                                'controller' => 'Photo',
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
                                'controller' => 'Photo',
                                'action' => 'index',
                            ],
                        ],
                    ],
                    'weekly' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/weekly',
                            'defaults' => [
                                'controller' => 'Photo',
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
                                'controller' => 'Photo',
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
                                'controller' => 'Photo',
                                'action' => 'removeProfilePhoto',
                            ],
                        ],
                    ],
                ],
                'priority' => 100
            ],
            'admin_photo' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/admin/photo',
                    'defaults' => [
                        '__NAMESPACE__' => 'Photo\Controller',
                        'controller' => 'AlbumAdmin',
                        'action' => 'index'
                    ]
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
                                'controller' => 'AlbumAdmin',
                                'action' => 'index'
                            ],
                        ],
                    ],
                    'album_index' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/album[/:album_id]',
                            'defaults' => [
                                'controller' => 'AlbumAdmin',
                                'action' => 'page'
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
                                'controller' => 'AlbumAdmin',
                                'action' => 'page'
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
                                'controller' => 'AlbumAdmin',
                                'action' => 'edit'
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
                                'controller' => 'AlbumAdmin',
                                'action' => 'create'
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
                                'controller' => 'AlbumAdmin',
                                'action' => 'add'
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
                                'controller' => 'AlbumAdmin',
                                'action' => 'import'
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
                                'controller' => 'AlbumAdmin',
                                'action' => 'upload'
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
                                'controller' => 'AlbumAdmin',
                                'action' => 'move'
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
                                'controller' => 'AlbumAdmin',
                                'action' => 'delete'
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
                                'controller' => 'AlbumAdmin',
                                'action' => 'cover'
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
                                'controller' => 'PhotoAdmin',
                                'action' => 'index'
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
                                'controller' => 'PhotoAdmin',
                                'action' => 'move'
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
                                'controller' => 'PhotoAdmin',
                                'action' => 'delete'
                            ],
                            'constraints' => [
                                'photo_id' => '[0-9]+',
                            ],
                        ],
                    ],
                ],
                'priority' => 100
            ],
            'api_photo' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/api/photo',
                    'defaults' => [
                        '__NAMESPACE__' => 'Photo\Controller',
                        'controller' => 'Api',
                        'action' => 'index'
                    ]
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'album_list' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/album/:album_id',
                            'defaults' => [
                                'action' => 'list'
                            ],
                            'constraints' => [
                                'album_id' => '[0-9]+',
                            ],
                        ],
                    ],
                ],
                'priority' => 100
            ]
        ],
    ],
    'controllers' => [
        'factories' => [
            'Photo\Controller\Photo' => function (ContainerInterface $serviceManager) {
                $photoService = $serviceManager->getServiceLocator()->get("photo_service_photo");
                $albumService = $serviceManager->getServiceLocator()->get("photo_service_album");
                return new PhotoController($photoService, $albumService);
            },
            'Photo\Controller\Tag' => function (ContainerInterface $serviceManager) {
                $photoService = $serviceManager->getServiceLocator()->get("photo_service_photo");
                return new TagController($photoService);
            },
            'Photo\Controller\AlbumAdmin' => function (ContainerInterface $serviceManager) {
                $adminService = $serviceManager->getServiceLocator()->get("photo_service_admin");
                $albumService = $serviceManager->getServiceLocator()->get("photo_service_album");
                return new AlbumAdminController($adminService, $albumService);
            },
            'Photo\Controller\Album' => function (ContainerInterface $serviceManager) {
                $albumService = $serviceManager->getServiceLocator()->get("photo_service_album");
                $pageCache = $serviceManager->getServiceLocator()->get('album_page_cache');
                $photoConfig = $serviceManager->getServiceLocator()->get('config')['photo'];
                return new AlbumController($albumService, $pageCache, $photoConfig);
            },
            'Photo\Controller\PhotoAdmin' => function (ContainerInterface $serviceManager) {
                $photoService = $serviceManager->getServiceLocator()->get("photo_service_photo");
                $entityManager = $serviceManager->getServiceLocator()->get('photo_doctrine_em');
                return new PhotoAdminController($photoService, $entityManager);
            },
            'Photo\Controller\Api' => function () {
                return new ApiController();
            },
        ]
    ],
    'controller_plugins' => [
        'factories' => [
            'AlbumPlugin' => function (ContainerInterface $serviceManager) {
                $photoService = $serviceManager->getServiceLocator()->get("photo_service_photo");
                $albumService = $serviceManager->getServiceLocator()->get("photo_service_album");
                return new AlbumPlugin($photoService, $albumService);
            }
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            'photo' => __DIR__ . '/../view/'
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
                'paths' => [__DIR__ . '/../src/Photo/Model/']
            ],
            'orm_default' => [
                'drivers' => [
                    'Photo\Model' => 'photo_entities'
                ]
            ]
        ]
    ],
    'console' => [
        'router' => [
            'routes' => [
                'weeklyphoto' => [
                    'options' => [
                        'route' => 'photo weeklyphoto',
                        'defaults' => [
                            'controller' => 'Photo\Controller\PhotoAdmin',
                            'action' => 'weeklyPhoto'
                        ]
                    ]
                ],
                'migrate_aspect_ratio' => [
                    'options' => [
                        'route' => 'photo aspectratio',
                        'defaults' => [
                            'controller' => 'Photo\Controller\PhotoAdmin',
                            'action' => 'migrateAspectRatios'
                        ]
                    ]
                ],
            ]
        ]
    ]
];
