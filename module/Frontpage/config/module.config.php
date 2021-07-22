<?php

use Frontpage\Controller\{
    AdminController,
    FrontpageController,
    NewsAdminController,
    OrganController,
    PageAdminController,
    PageController,
    PollAdminController,
    PollController,
};
use Frontpage\Controller\Factory\{
    AdminControllerFactory,
    FrontpageControllerFactory,
    NewsAdminControllerFactory,
    OrganControllerFactory,
    PageAdminControllerFactory,
    PageControllerFactory,
    PollAdminControllerFactory,
    PollControllerFactory,
};

return [
    'router' => [
        'routes' => [
            'home' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/',
                    'defaults' => [
                        'controller' => FrontpageController::class,
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
                                'controller' => PageController::class,
                                'action' => 'page',
                            ],
                        ],
                        'priority' => -1,
                    ],
                    'organ' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => 'association/:type/:abbr',
                            'constraints' => [
                                'type' => 'committee|fraternity|avc|avw|rva|kkk',
                                'abbr' => '[^/]*',
                            ],
                            'defaults' => [
                                'controller' => OrganController::class,
                                'action' => 'organ',
                            ],
                        ],
                        'priority' => 100,
                    ],
                    'committee_list' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => 'association/committees',
                            'defaults' => [
                                'controller' => OrganController::class,
                                'action' => 'committeeList',
                            ],
                        ],
                        'priority' => 100,
                    ],
                    'fraternity_list' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => 'association/fraternities',
                            'defaults' => [
                                'controller' => OrganController::class,
                                'action' => 'fraternityList',
                            ],
                        ],
                        'priority' => 100,
                    ],
                ],
            ],
            'admin_page' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/admin/page',
                    'defaults' => [
                        'controller' => PageAdminController::class,
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
                'priority' => 100,
            ],
            'poll' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/poll',
                    'defaults' => [
                        'controller' => PollController::class,
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
                                'action' => 'index',
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
                    'comment' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:poll_id]/comment',
                            'constraints' => [
                                'poll_id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'comment',
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'admin_poll' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/admin/poll',
                    'defaults' => [
                        'controller' => PollAdminController::class,
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
                    'delete' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:poll_id]/delete',
                            'constraints' => [
                                'poll_id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'delete',
                            ],
                        ],
                    ],
                    'approve' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:poll_id]/approve',
                            'constraints' => [
                                'poll_id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'approve',
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'admin_news' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/admin/news',
                    'defaults' => [
                        'controller' => NewsAdminController::class,
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
                            'route' => '[/:item_id]/edit',
                            'constraints' => [
                                'item_id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'edit',
                            ],
                        ],
                    ],
                    'delete' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:item_id]/delete',
                            'constraints' => [
                                'item_id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'delete',
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'admin' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/admin[/]',
                    'defaults' => [
                        'controller' => AdminController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'priority' => 100,
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            AdminController::class => AdminControllerFactory::class,
            FrontpageController::class => FrontpageControllerFactory::class,
            NewsAdminController::class => NewsAdminControllerFactory::class,
            OrganController::class => OrganControllerFactory::class,
            PageAdminController::class => PageAdminControllerFactory::class,
            PageController::class => PageControllerFactory::class,
            PollAdminController::class => PollAdminControllerFactory::class,
            PollController::class => PollControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'Frontpage' => __DIR__ . '/../view',
        ],
        'template_map' => [
            'page-admin/edit' => __DIR__ . '/../view/frontpage/page-admin/edit.phtml',
            'news-admin/edit' => __DIR__ . '/../view/frontpage/news-admin/edit.phtml',
            'organ/committee-list' => __DIR__ . '/../view/frontpage/organ/committee-list.phtml',
            'organ/fraternity-list' => __DIR__ . '/../view/frontpage/organ/fraternity-list.phtml',
        ],
    ],
    'doctrine' => [
        'driver' => [
            'frontpage_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Model/'],
            ],
            'orm_default' => [
                'drivers' => [
                    'Frontpage\Model' => 'frontpage_entities',
                ],
            ],
        ],
    ],
];
