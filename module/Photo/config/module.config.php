<?php

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
        'invokables' => [
            'Photo\Controller\Photo' => 'Photo\Controller\PhotoController',
            'Photo\Controller\Album' => 'Photo\Controller\AlbumController',
            'Photo\Controller\AlbumAdmin' => 'Photo\Controller\AlbumAdminController',
            'Photo\Controller\PhotoAdmin' => 'Photo\Controller\PhotoAdminController',
            'Photo\Controller\Tag' => 'Photo\Controller\TagController',
            'Photo\Controller\Api' => 'Photo\Controller\ApiController',
        ]
    ],
    'controller_plugins' => [
        'invokables' => [
            'AlbumPlugin' => 'Photo\Controller\Plugin\AlbumPlugin',
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
