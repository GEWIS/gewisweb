<?php
return [
    'controllers' => [
        'invokables' => [
            'Frontpage\Controller\Frontpage' => 'Frontpage\Controller\FrontpageController',
            'Frontpage\Controller\Page' => 'Frontpage\Controller\PageController',
            'Frontpage\Controller\PageAdmin' => 'Frontpage\Controller\PageAdminController',
            'Frontpage\Controller\Poll' => 'Frontpage\Controller\PollController',
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
            'poll' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/poll',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Frontpage\Controller',
                        'controller' => 'Poll',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'history' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/history',
                            'defaults' => array(
                                'action' => 'history',
                            ),
                        ),
                    ),
                    'request' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/request',
                            'defaults' => array(
                                'action' => 'request',
                            ),
                        ),
                    ),
                    'view' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[/:poll_id]/view',
                            'constraints' => array(
                                'poll_id' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'view',
                            ),
                        ),
                    ),
                    'vote' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[/:poll_id]/vote',
                            'constraints' => array(
                                'poll_id' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'vote',
                            ),
                        ),
                    ),
                ),
                'priority' => 100
            ),
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
