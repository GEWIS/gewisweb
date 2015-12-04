<?php
return [
    'controllers' => [
        'invokables' => [
            'Frontpage\Controller\Frontpage' => 'Frontpage\Controller\FrontpageController',
            'Frontpage\Controller\Page' => 'Frontpage\Controller\PageController',
            'Frontpage\Controller\PageAdmin' => 'Frontpage\Controller\PageAdminController',
            'Frontpage\Controller\Poll' => 'Frontpage\Controller\PollController',
            'Frontpage\Controller\PollAdmin' => 'Frontpage\Controller\PollAdminController',
        ],
    ],
    'router' => [
        'routes' => [
            'home' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/',
                    'defaults' => [
                        '__NAMESPACE__' => 'Frontpage\Controller',
                        'controller' => 'Frontpage',
                        'action' => 'home',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'page' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[:category[/:sub_category][/:name]][/]',
                            'constraints' => [
                                'category' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'sub_category' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'name' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Frontpage\Controller',
                                'controller' => 'Page',
                                'action' => 'page',
                            ],
                        ],
                        'priority' => -1
                    ],
                ],
            ],
            'admin_page' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/admin/page',
                    'defaults' => [
                        '__NAMESPACE__' => 'Frontpage\Controller',
                        'controller' => 'PageAdmin',
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'create' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/create',
                            'defaults' => [
                                'action' => 'create',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:page_id]/edit',
                            'constraints' => [
                                'page_id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'edit',
                            ],
                        ],
                    ],
                    'delete' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:page_id]/delete',
                            'constraints' => [
                                'page_id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'delete',
                            ],
                        ],
                    ],
                    'upload' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/upload',
                            'defaults' => [
                                'action' => 'upload',
                            ],
                        ],
                    ],
                ],
                'priority' => 100
            ],
            'poll' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/poll',
                    'defaults' => [
                        '__NAMESPACE__' => 'Frontpage\Controller',
                        'controller' => 'Poll',
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'history' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/history[/:page]',
                            'constraints' => [
                                'page' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'history',
                            ],
                        ],
                    ],
                    'request' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/request',
                            'defaults' => [
                                'action' => 'request',
                            ],
                        ],
                    ],
                    'view' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:poll_id]/view',
                            'constraints' => [
                                'poll_id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'view',
                            ],
                        ],
                    ],
                    'vote' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:poll_id]/vote',
                            'constraints' => [
                                'poll_id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'vote',
                            ],
                        ],
                    ],
                ],
                'priority' => 100
            ],
            'admin_poll' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/admin/poll',
                    'defaults' => [
                        '__NAMESPACE__' => 'Frontpage\Controller',
                        'controller' => 'PollAdmin',
                        'action' => 'list',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'list' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/list[/:page]',
                            'constraints' => [
                                'page' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'list',
                            ],
                        ],
                    ],
                ],
                'priority' => 100
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'Frontpage' => __DIR__ . '/../view',
        ],
        'template_map' => [
            'page-admin/edit' => __DIR__ . '/../view/frontpage/page-admin/edit.phtml',
        ],
    ],
    'doctrine' => [
        'driver' => [
            'frontpage_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Frontpage/Model/']
            ],
            'orm_default' => [
                'drivers' => [
                    'Frontpage\Model' => 'frontpage_entities'
                ]
            ]
        ]
    ],
];
