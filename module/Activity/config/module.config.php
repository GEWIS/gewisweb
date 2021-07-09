<?php

use Activity\Controller\ActivityCalendarController;
use Activity\Controller\AdminCategoryController;
use Interop\Container\ContainerInterface;

return [
    'router' => [
        'routes' => [
            'activity' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/activity',
                    'defaults' => [
                        '__NAMESPACE__' => 'Activity\Controller',
                        'controller' => 'Activity',
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
                                'action' => 'view'
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
                                        'action' => 'viewSignupList'
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
                                'action' => 'signup'
                            ]
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
                                'action' => 'externalSignup'
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
                                'action' => 'signoff'
                            ]
                        ],
                    ],
                    'create' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/create',
                            'defaults' => [
                                'action' => 'create'
                            ]
                        ]
                    ],
                    'career' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/career',
                            'defaults' => [
                                'action' => 'index',
                                'category' => 'career'
                            ]
                        ]
                    ],
                    'my' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/my',
                            'defaults' => [
                                'action' => 'index',
                                'category' => 'my'
                            ]
                        ]
                    ],
                    'archive' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/archive',
                            'defaults' => [
                                'action' => 'archive'
                            ]
                        ]
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
                'priority' => 100
            ],
            'activity_admin' => [
                'priority' => 100,
                'type' => 'Literal',
                'options' => [
                    'route' => '/admin/activity',
                    'defaults' => [
                        '__NAMESPACE__' => 'Activity\Controller',
                        'controller' => 'admin',
                        'action' => 'view'
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
                                'controller' => 'admin',
                                'action' => 'view'
                            ]
                        ]
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
                                'controller' => 'admin',
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
                                'controller' => 'admin',
                                'action' => 'externalSignup',
                            ]
                        ]
                    ],
                    'externalSignoff' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/externalSignoff/:id',
                            'constraints' => [
                                'id' => '\d+',
                            ],
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'externalSignoff',
                            ]
                        ]
                    ],
                    'update' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/update/:id',
                            'constraints' => [
                                'id' => '\d+',
                            ],
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'update'
                            ]
                        ]
                    ]
                ]

            ],
            'activity_calendar' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/activity/calendar/',
                    'defaults' => [
                        '__NAMESPACE__' => 'Activity\Controller',
                        'controller' => 'activityCalendar',
                        'action' => 'index'
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
                            ]
                        ]
                    ],
                    'approve' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => 'approve',
                            'defaults' => [
                                'action' => 'approve',
                            ]
                        ]
                    ],
                    'create' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => 'create',
                            'defaults' => [
                                'action' => 'create'
                            ]
                        ]
                    ],
                ]

            ],
            'activity_admin_approval' => [
                'priority' => 150,
                'type' => 'Literal',
                'options' => [
                    'route' => '/admin/activity/approval',
                    'defaults' => [
                        '__NAMESPACE__' => 'Activity\Controller',
                        'controller' => 'adminApproval',
                    ]
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'view' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/view/[:id]',
                            'defaults' => [
                                'controller' => 'adminApproval',
                                'action' => 'view'
                            ]
                        ]
                    ],
                    'proposal' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/proposal/[:id]',
                            'defaults' => [
                                'controller' => 'adminApproval',
                                'action' => 'viewProposal'
                            ]
                        ]
                    ],
                    'apply_proposal' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/proposal/[:id]/apply',
                            'defaults' => [
                                'controller' => 'adminApproval',
                                'action' => 'applyProposal'
                            ]
                        ]
                    ],
                    'revoke_proposal' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/proposal/[:id]/revoke',
                            'defaults' => [
                                'controller' => 'adminApproval',
                                'action' => 'revokeProposal'
                            ]
                        ]
                    ],
                    'approve' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/approve/[:id]',
                            'defaults' => [
                                'controller' => 'adminApproval',
                                'action' => 'approve'
                            ]
                        ]
                    ],
                    'disapprove' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/disapprove/[:id]',
                            'defaults' => [
                                'controller' => 'adminApproval',
                                'action' => 'disapprove'
                            ]
                        ]
                    ],
                    'reset' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/reset/[:id]',
                            'defaults' => [
                                'controller' => 'adminApproval',
                                'action' => 'reset'
                            ]
                        ]
                    ]
                ],
            ],
            'activity_admin_categories' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/admin/activity/categories',
                    'defaults' => [
                        '__NAMESPACE__' => 'Activity\Controller',
                        'controller' => 'adminCategory',
                        'action' => 'index'
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'add' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/add',
                            'defaults' => [
                                'controller' => 'adminCategory',
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
                                'controller' => 'adminCategory',
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
                                'controller' => 'adminCategory',
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
                        '__NAMESPACE__' => 'Activity\Controller',
                        'controller' => 'Api',
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
                                'action' => 'list'
                            ]
                        ]
                    ],
                    'view' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/view/[:id]',
                            'constraints' => [
                                'action' => '[0-9]*',
                            ],
                            'defaults' => [
                                'action' => 'view'
                            ]
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
                                'action' => 'signup'
                            ]
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
                                'action' => 'signoff'
                            ]
                        ],
                    ],
                    'signedup' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/signedup',
                            'defaults' => [
                                'action' => 'signedup'
                            ]
                        ],
                    ],
                ],
                'priority' => 100
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            'Activity\Controller\Activity' => function (ContainerInterface $serviceManager) {
                $activityService = $serviceManager->getServiceLocator()->get('activity_service_activity');
                $activityQueryService = $serviceManager->getServiceLocator()->get('activity_service_activityQuery');
                $signupService = $serviceManager->getServiceLocator()->get('activity_service_signup');
                $signupListQueryService = $serviceManager->getServiceLocator()->get('activity_service_signupListQuery');
                return new Activity\Controller\ActivityController($activityService, $activityQueryService, $signupService, $signupListQueryService);
            },
            'Activity\Controller\AdminApproval' => function (ContainerInterface $serviceManager) {
                $activityService = $serviceManager->getServiceLocator()->get('activity_service_activity');
                $activityQueryService = $serviceManager->getServiceLocator()->get('activity_service_activityQuery');
                return new Activity\Controller\AdminApprovalController($activityService, $activityQueryService);
            },
            'Activity\Controller\AdminCategory' => function (ContainerInterface $serviceManager) {
                $categoryService = $serviceManager->getServiceLocator()->get('activity_service_category');
                return new AdminCategoryController($categoryService);
            },
            'Activity\Controller\Api' => function (ContainerInterface $serviceManager) {
                $activityQueryService = $serviceManager->getServiceLocator()->get('activity_service_activityQuery');
                $signupService = $serviceManager->getServiceLocator()->get('activity_service_signup');
                return new Activity\Controller\ApiController($activityQueryService, $signupService);
            },
            'Activity\Controller\Admin' => function (ContainerInterface $serviceManager) {
                $activityService = $serviceManager->getServiceLocator()->get('activity_service_activity');
                $activityQueryService = $serviceManager->getServiceLocator()->get('activity_service_activityQuery');
                $signupService = $serviceManager->getServiceLocator()->get('activity_service_signup');
                $signupListQueryService = $serviceManager->getServiceLocator()->get('activity_service_signupListQuery');
                return new Activity\Controller\AdminController($activityService, $activityQueryService, $signupService, $signupListQueryService);
            },
            'Activity\Controller\ActivityCalendar' => function (ContainerInterface $serviceManager) {
                $calendarService = $serviceManager->getServiceLocator()->get('activity_service_calendar');
                return new ActivityCalendarController($calendarService);
            },
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            'activity' => __DIR__ . '/../view/'
        ]
    ],
    'console' => [
        'router' => [
            'routes' => [
                'activity_calendar_update' => [
                    'options' => [
                        'route' => 'activity calendar notify',
                        'defaults' => [
                            'controller' => 'Activity\Controller\ActivityCalendar',
                            'action' => 'sendNotifications'
                        ]
                    ]
                ],
            ]
        ]
    ],
    'doctrine' => [
        'driver' => [
            'activity_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Activity/Model/']
            ],
            'orm_default' => [
                'drivers' => [
                    'Activity\Model' => 'activity_entities'
                ]
            ]
        ]
    ]
];
