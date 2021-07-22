<?php

use Activity\Command\CalendarNotify;
use Activity\Controller\{
    ActivityCalendarController,
    ActivityController,
    AdminApprovalController,
    AdminCategoryController,
    AdminController,
    ApiController,
};
use Activity\Controller\Factory\{
    ActivityCalendarControllerFactory,
    ActivityControllerFactory,
    AdminApprovalControllerFactory,
    AdminCategoryControllerFactory,
    AdminControllerFactory,
    ApiControllerFactory,
};

return [
    'router' => [
        'routes' => [
            'activity' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/activity',
                    'defaults' => [
                        'controller' => ActivityController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'view' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/view/:id',
                            'constraints' => [
                                'id' => '\d+',
                            ],
                            'defaults' => [
                                'action' => 'view',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'signuplist' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/:signupList',
                                    'constraints' => [
                                        'signupList' => '\d+',
                                    ],
                                    'defaults' => [
                                        'action' => 'viewSignupList',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'signup' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/signup/:id/:signupList',
                            'constraints' => [
                                'id' => '\d+',
                                'signupList' => '\d+',
                            ],
                            'defaults' => [
                                'action' => 'signup',
                            ],
                        ],
                    ],
                    'externalSignup' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/externalSignup/:id/:signupList',
                            'constraints' => [
                                'id' => '\d+',
                                'signupList' => '\d+',
                            ],
                            'defaults' => [
                                'action' => 'externalSignup',
                            ],
                        ],
                    ],
                    'signoff' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/signoff/:id/:signupList',
                            'constraints' => [
                                'id' => '\d+',
                                'signupList' => '\d+',
                            ],
                            'defaults' => [
                                'action' => 'signoff',
                            ],
                        ],
                    ],
                    'create' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/create',
                            'defaults' => [
                                'action' => 'create',
                            ],
                        ],
                    ],
                    'career' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/career',
                            'defaults' => [
                                'action' => 'index',
                                'category' => 'career',
                            ],
                        ],
                    ],
                    'my' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/my',
                            'defaults' => [
                                'action' => 'index',
                                'category' => 'my',
                            ],
                        ],
                    ],
                    'archive' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/archive',
                            'defaults' => [
                                'action' => 'archive',
                            ],
                        ],
                    ],
                    // Route for categorizing activities by association year.
                    'year' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/archive[/:year]',
                            'constraints' => [
                                'year' => '\d{4}',
                            ],
                            'defaults' => [
                                'action' => 'archive',
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'activity_admin' => [
                'priority' => 100,
                'type' => 'Literal',
                'options' => [
                    'route' => '/admin/activity',
                    'defaults' => [
                        'controller' => AdminController::class,
                        'action' => 'view',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'index' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:page]',
                            'constraints' => [
                                'page' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'view',
                            ],
                        ],
                    ],
                    'participants' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/participants/:id[/:signupList]',
                            'constraints' => [
                                'id' => '\d+',
                                'signupList' => '\d+',
                            ],
                            'defaults' => [
                                'action' => 'participants',
                            ],
                        ],
                    ],
                    'adminSignup' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/adminSignup/:id/:signupList',
                            'constraints' => [
                                'id' => '\d+',
                                'signupList' => '\d+',
                            ],
                            'defaults' => [
                                'action' => 'externalSignup',
                            ],
                        ],
                    ],
                    'externalSignoff' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/externalSignoff/:id',
                            'constraints' => [
                                'id' => '\d+',
                            ],
                            'defaults' => [
                                'action' => 'externalSignoff',
                            ],
                        ],
                    ],
                    'update' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/update/:id',
                            'constraints' => [
                                'id' => '\d+',
                            ],
                            'defaults' => [
                                'action' => 'update',
                            ],
                        ],
                    ],
                ],
            ],
            'activity_calendar' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/activity/calendar/',
                    'defaults' => [
                        'controller' => ActivityCalendarController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'delete' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => 'delete',
                            'defaults' => [
                                'action' => 'delete',
                            ],
                        ],
                    ],
                    'approve' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => 'approve',
                            'defaults' => [
                                'action' => 'approve',
                            ],
                        ],
                    ],
                    'create' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => 'create',
                            'defaults' => [
                                'action' => 'create',
                            ],
                        ],
                    ],
                ],
            ],
            'activity_admin_approval' => [
                'priority' => 150,
                'type' => 'Literal',
                'options' => [
                    'route' => '/admin/activity/approval',
                    'defaults' => [
                        'controller' => AdminApprovalController::class,
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'view' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/view/[:id]',
                            'defaults' => [
                                'action' => 'view',
                            ],
                        ],
                    ],
                    'proposal' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/proposal/[:id]',
                            'defaults' => [
                                'action' => 'viewProposal',
                            ],
                        ],
                    ],
                    'apply_proposal' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/proposal/[:id]/apply',
                            'defaults' => [
                                'action' => 'applyProposal',
                            ],
                        ],
                    ],
                    'revoke_proposal' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/proposal/[:id]/revoke',
                            'defaults' => [
                                'action' => 'revokeProposal',
                            ],
                        ],
                    ],
                    'approve' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/approve/[:id]',
                            'defaults' => [
                                'action' => 'approve',
                            ],
                        ],
                    ],
                    'disapprove' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/disapprove/[:id]',
                            'defaults' => [
                                'action' => 'disapprove',
                            ],
                        ],
                    ],
                    'reset' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/reset/[:id]',
                            'defaults' => [
                                'action' => 'reset',
                            ],
                        ],
                    ],
                ],
            ],
            'activity_admin_categories' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/admin/activity/categories',
                    'defaults' => [
                        'controller' => AdminCategoryController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'add' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/add',
                            'defaults' => [
                                'action' => 'add',
                            ],
                        ],
                    ],
                    'delete' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/delete/:id',
                            'constraints' => [
                                'id' => '\d+',
                            ],
                            'defaults' => [
                                'action' => 'delete',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/edit/:id',
                            'constraints' => [
                                'id' => '\d+',
                            ],
                            'defaults' => [
                                'action' => 'edit',
                            ],
                        ],
                    ],
                ],
            ],
            'activity_api' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/api/activity',
                    'defaults' => [
                        'controller' => ApiController::class,
                        'action' => 'list',
                    ],
                ],
                'may_terminate' => false,
                'child_routes' => [
                    'list' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/list',
                            'defaults' => [
                                'action' => 'list',
                            ],
                        ],
                    ],
                    'view' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/view/[:id]',
                            'constraints' => [
                                'action' => '[0-9]*',
                            ],
                            'defaults' => [
                                'action' => 'view',
                            ],
                        ],
                    ],
                    'signup' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/signup/[:id]',
                            'constraints' => [
                                'id' => '[0-9]*',
                            ],
                            'defaults' => [
                                'action' => 'signup',
                            ],
                        ],
                    ],
                    'signoff' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/signoff/[:id]',
                            'constraints' => [
                                'id' => '[0-9]*',
                            ],
                            'defaults' => [
                                'action' => 'signoff',
                            ],
                        ],
                    ],
                    'signedup' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/signedup',
                            'defaults' => [
                                'action' => 'signedup',
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
            ActivityCalendarController::class => ActivityCalendarControllerFactory::class,
            ActivityController::class => ActivityControllerFactory::class,
            AdminApprovalController::class => AdminApprovalControllerFactory::class,
            AdminCategoryController::class => AdminCategoryControllerFactory::class,
            AdminController::class => AdminControllerFactory::class,
            ApiController::class => ApiControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'activity' => __DIR__ . '/../view/',
        ],
    ],
    'laminas-cli' => [
        'commands' => [
            'activity:calendar:notify' => CalendarNotify::class,
        ],
    ],
    'doctrine' => [
        'driver' => [
            'activity_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Model/'],
            ],
            'orm_default' => [
                'drivers' => [
                    'Activity\Model' => 'activity_entities',
                ],
            ],
        ],
    ],
];
