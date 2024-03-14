<?php

declare(strict_types=1);

namespace Frontpage;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Frontpage\Controller\AdminController;
use Frontpage\Controller\Factory\AdminControllerFactory;
use Frontpage\Controller\Factory\FrontpageControllerFactory;
use Frontpage\Controller\Factory\InfimumControllerFactory;
use Frontpage\Controller\Factory\NewsAdminControllerFactory;
use Frontpage\Controller\Factory\OrganControllerFactory;
use Frontpage\Controller\Factory\PageAdminControllerFactory;
use Frontpage\Controller\Factory\PageControllerFactory;
use Frontpage\Controller\Factory\PollAdminControllerFactory;
use Frontpage\Controller\Factory\PollControllerFactory;
use Frontpage\Controller\FrontpageController;
use Frontpage\Controller\InfimumController;
use Frontpage\Controller\NewsAdminController;
use Frontpage\Controller\OrganController;
use Frontpage\Controller\PageAdminController;
use Frontpage\Controller\PageController;
use Frontpage\Controller\PollAdminController;
use Frontpage\Controller\PollController;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type' => Literal::class,
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
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[:category[/:sub_category][/:name][/]]',
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
                        'type' => Segment::class,
                        'options' => [
                            'route' => 'association/:type/:abbr',
                            'constraints' => [
                                'type' => 'committee|fraternity|avc|avw|rva|kcc',
                                'abbr' => '[^/]+',
                            ],
                            'defaults' => [
                                'controller' => OrganController::class,
                                'action' => 'organ',
                            ],
                        ],
                        'priority' => 100,
                    ],
                    'committee_list' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => 'association/committees',
                            'defaults' => [
                                'controller' => OrganController::class,
                                'action' => 'committeeList',
                            ],
                        ],
                        'priority' => 100,
                    ],
                    'committee_historical_list' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => 'association/history/committees',
                            'defaults' => [
                                'controller' => OrganController::class,
                                'action' => 'historicalCommitteeList',
                            ],
                        ],
                        'priority' => 100,
                    ],
                    'fraternity_list' => [
                        'type' => Literal::class,
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
                'type' => Segment::class,
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
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/create',
                            'defaults' => [
                                'action' => 'create',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => Segment::class,
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
                        'type' => Segment::class,
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
                        'type' => Literal::class,
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
                'type' => Literal::class,
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
                        'type' => Segment::class,
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
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/request',
                            'defaults' => [
                                'action' => 'request',
                            ],
                        ],
                    ],
                    'view' => [
                        'type' => Segment::class,
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
                        'type' => Segment::class,
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
                        'type' => Segment::class,
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
                'type' => Segment::class,
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
                        'type' => Segment::class,
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
                        'type' => Segment::class,
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
                        'type' => Segment::class,
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
                'type' => Segment::class,
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
                        'type' => Segment::class,
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
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/create',
                            'defaults' => [
                                'action' => 'create',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => Segment::class,
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
                        'type' => Segment::class,
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
                'type' => Segment::class,
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
            'infimum' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/api/infimum',
                    'defaults' => [
                        'controller' => InfimumController::class,
                        'action' => 'show',
                    ],
                ],
                'priority' => 100,
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            AdminController::class => AdminControllerFactory::class,
            FrontpageController::class => FrontpageControllerFactory::class,
            InfimumController::class => InfimumControllerFactory::class,
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
];
