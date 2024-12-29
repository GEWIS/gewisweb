<?php

declare(strict_types=1);

namespace Activity;

use Activity\Command\CalendarNotify;
use Activity\Command\DeleteOldSignups;
use Activity\Controller\ActivityCalendarController;
use Activity\Controller\ActivityController;
use Activity\Controller\AdminApprovalController;
use Activity\Controller\AdminCategoryController;
use Activity\Controller\AdminController;
use Activity\Controller\AdminOptionController;
use Activity\Controller\ApiController;
use Activity\Controller\Factory\ActivityCalendarControllerFactory;
use Activity\Controller\Factory\ActivityControllerFactory;
use Activity\Controller\Factory\AdminApprovalControllerFactory;
use Activity\Controller\Factory\AdminCategoryControllerFactory;
use Activity\Controller\Factory\AdminControllerFactory;
use Activity\Controller\Factory\AdminOptionControllerFactory;
use Activity\Controller\Factory\ApiControllerFactory;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use User\Listener\Authentication;

return [
    'router' => [
        'routes' => [
            'activity' => [
                'type' => Literal::class,
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
                        'type' => Segment::class,
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
                                'type' => Segment::class,
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
                        'type' => Segment::class,
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
                        'type' => Segment::class,
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
                        'type' => Segment::class,
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
                    'career' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/career',
                            'defaults' => [
                                'action' => 'index',
                                'category' => 'career',
                            ],
                        ],
                    ],
                    'my' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/my',
                            'defaults' => [
                                'action' => 'index',
                                'category' => 'my',
                            ],
                        ],
                    ],
                    'archive' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/archive',
                            'defaults' => [
                                'action' => 'archive',
                            ],
                        ],
                    ],
                    // Route for categorizing activities by association year.
                    'year' => [
                        'type' => Segment::class,
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
                'type' => Literal::class,
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
                        'type' => Segment::class,
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
                        'type' => Segment::class,
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
                        'type' => Segment::class,
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
                        'type' => Segment::class,
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
                    'create' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/create',
                            'defaults' => [
                                'action' => 'create',
                            ],
                        ],
                    ],
                    'update' => [
                        'type' => Segment::class,
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
                'type' => Literal::class,
                'options' => [
                    'route' => '/activity/calendar',
                    'defaults' => [
                        'controller' => ActivityCalendarController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'delete' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/delete',
                            'defaults' => [
                                'action' => 'delete',
                            ],
                        ],
                    ],
                    'approve' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/approve',
                            'defaults' => [
                                'action' => 'approve',
                            ],
                        ],
                    ],
                    'create' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/create',
                            'defaults' => [
                                'action' => 'create',
                            ],
                        ],
                    ],
                ],
            ],
            'activity_admin_approval' => [
                'priority' => 150,
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/activity/approval',
                    'defaults' => [
                        'controller' => AdminApprovalController::class,
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'view' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/view/[:id]',
                            'defaults' => [
                                'action' => 'view',
                            ],
                        ],
                    ],
                    'proposal' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/proposal/[:id]',
                            'defaults' => [
                                'action' => 'viewProposal',
                            ],
                        ],
                    ],
                    'apply_proposal' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/proposal/[:id]/apply',
                            'defaults' => [
                                'action' => 'applyProposal',
                            ],
                        ],
                    ],
                    'revoke_proposal' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/proposal/[:id]/revoke',
                            'defaults' => [
                                'action' => 'revokeProposal',
                            ],
                        ],
                    ],
                    'approve' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/approve/[:id]',
                            'defaults' => [
                                'action' => 'approve',
                            ],
                        ],
                    ],
                    'disapprove' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/disapprove/[:id]',
                            'defaults' => [
                                'action' => 'disapprove',
                            ],
                        ],
                    ],
                    'reset' => [
                        'type' => Segment::class,
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
                'type' => Literal::class,
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
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/add',
                            'defaults' => [
                                'action' => 'add',
                            ],
                        ],
                    ],
                    'delete' => [
                        'type' => Segment::class,
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
                        'type' => Segment::class,
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
            'activity_admin_options' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/activity/calendar',
                    'defaults' => [
                        'controller' => AdminOptionController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
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
                    'delete' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/delete/:id',
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'delete',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/edit/:id',
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'edit',
                            ],
                        ],
                    ],
                ],
            ],
            'activity_api' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/api/activity',
                    'defaults' => [
                        'controller' => ApiController::class,
                        'action' => 'list',
                        'auth_type' => Authentication::AUTH_API,
                    ],
                ],
                'may_terminate' => false,
                'child_routes' => [
                    'list' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/list',
                            'defaults' => [
                                'action' => 'list',
                            ],
                        ],
                    ],
                    'view' => [
                        'type' => Segment::class,
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
            AdminOptionController::class => AdminOptionControllerFactory::class,
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
            'activity:gdpr:delete-old-signups' => DeleteOldSignups::class,
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
