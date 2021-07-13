<?php

use Frontpage\Controller\AdminController;
use Frontpage\Controller\FrontpageController;
use Frontpage\Controller\NewsAdminController;
use Frontpage\Controller\OrganController;
use Frontpage\Controller\PageAdminController;
use Frontpage\Controller\PageController;
use Frontpage\Controller\PollAdminController;
use Frontpage\Controller\PollController;
use Laminas\ServiceManager\ServiceLocatorInterface;

return [
    'controllers' => [
        'factories' => [
            'Frontpage\Controller\Frontpage' => function (ServiceLocatorInterface $sm) {
                $frontpageService = $sm->get('frontpage_service_frontpage');

                return new FrontpageController($frontpageService);
            },
            'Frontpage\Controller\Organ' => function (ServiceLocatorInterface $sm) {
                $organService = $sm->get('decision_service_organ');
                $activityQueryService = $sm->get('activity_service_activityQuery');

                return new OrganController($organService, $activityQueryService);
            },
            'Frontpage\Controller\Page' => function (ServiceLocatorInterface $sm) {
                $pageService = $sm->get('frontpage_service_page');

                return new PageController($pageService);
            },
            'Frontpage\Controller\PageAdmin' => function (ServiceLocatorInterface $sm) {
                $pageService = $sm->get('frontpage_service_page');

                return new PageAdminController($pageService);
            },
            'Frontpage\Controller\Poll' => function (ServiceLocatorInterface $sm) {
                $pollService = $sm->get('frontpage_service_poll');
                $pollCommentForm = $sm->get('frontpage_form_poll_comment');

                return new PollController($pollService, $pollCommentForm);
            },
            'Frontpage\Controller\PollAdmin' => function (ServiceLocatorInterface $sm) {
                $pollService = $sm->get('frontpage_service_poll');

                return new PollAdminController($pollService);
            },
            'Frontpage\Controller\NewsAdmin' => function (ServiceLocatorInterface $sm) {
                $newsService = $sm->get('frontpage_service_news');

                return new NewsAdminController($newsService);
            },
            'Frontpage\Controller\Admin' => function () {
                return new AdminController();
            },],
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
                                'action' => 'organ',
                                'controller' => 'Organ',
                            ],
                        ],
                        'priority' => 100,
                    ],
                    'committee_list' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => 'association/committees',
                            'defaults' => [
                                'action' => 'committeeList',
                                'controller' => 'Organ',
                            ],
                        ],
                        'priority' => 100,
                    ],
                    'fraternity_list' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => 'association/fraternities',
                            'defaults' => [
                                'action' => 'fraternityList',
                                'controller' => 'Organ',
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
                'priority' => 100,
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
                        '__NAMESPACE__' => 'Frontpage\Controller',
                        'controller' => 'NewsAdmin',
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
                        '__NAMESPACE__' => 'Frontpage\Controller',
                        'controller' => 'Admin',
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'priority' => 100,
            ],
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
