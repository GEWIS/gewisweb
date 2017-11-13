<?php
return [
    'router' => [
        'routes' => [
            'activity' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/activity',
                    'defaults' => [
                        '__NAMESPACE__' => 'Activity\Controller',
                        'controller'    => 'Activity',
                        'action'        => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'view' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/view/[:id]',
                            'constraints' => [
                                'action'     => '[0-9]*',
                            ],
                            'defaults' => [
                                'action' => 'view'
                            ]
                        ],
                    ],
                    'signup' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/signup/[:id]',
                            'constraints' => [
                                'action'     => '[0-9]*',
                            ],
                            'defaults' => [
                                'action' => 'signup'
                            ]
                        ],
                    ],
                    'externalSignup' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/externalSignup/:id',
                            'constraints' => [
                                'actions' => '[0-9]*',
                            ],
                            'defaults' => [
                                'action' => 'externalSignup'
                            ],
                        ],
                    ],
                    'signoff' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/signoff/[:id]',
                            'constraints' => [
                                'action'     => '[0-9]*',
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
                    'touch' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/touch',
                            'defaults' => [
                                'action' => 'touch'
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
                            'route' => '/:id/participants',
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'participants',
                            ]
                        ]
                    ],
                    'adminSignup' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:id/adminSignup',
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'externalSignup',
                            ]
                        ]
                    ],
                    'externalSignoff' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:id/externalSignoff',
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'externalSignoff',
                            ]
                        ]
                    ],
                    'exportpdf' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:id/export/pdf',
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'exportpdf',
                            ]
                        ]
                    ],
                    'update' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:id/update',
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
            'activity_api' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/api/activity',
                    'defaults' => [
                        '__NAMESPACE__' => 'Activity\Controller',
                        'controller'    => 'Api',
                        'action'        => 'list',
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
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/view/[:id]',
                            'constraints' => [
                                'action'     => '[0-9]*',
                            ],
                            'defaults' => [
                                'action' => 'view'
                            ]
                        ],
                    ],
                    'signup' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/signup/[:id]',
                            'constraints' => [
                                'id'     => '[0-9]*',
                            ],
                            'defaults' => [
                                'action' => 'signup'
                            ]
                        ],
                    ],
                    'signoff' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/signoff/[:id]',
                            'constraints' => [
                                'id'     => '[0-9]*',
                            ],
                            'defaults' => [
                                'action' => 'signoff'
                            ]
                        ],
                    ],
                    'signedup' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/signedup',
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
        'invokables' => [
            'Activity\Controller\Activity' => 'Activity\Controller\ActivityController',
            'Activity\Controller\AdminApproval' => 'Activity\Controller\AdminApprovalController',
            'Activity\Controller\Api' => 'Activity\Controller\ApiController',
            'Activity\Controller\Admin' => 'Activity\Controller\AdminController',
            'Activity\Controller\ActivityCalendar' => 'Activity\Controller\ActivityCalendarController',
        ],
        'factories' => [
            'Activity\Controller\Activity' => function ($sm) {
                $controller = new Activity\Controller\ActivityController;
                $activity = $sm->getServiceLocator()->get('activity_service');
                $controller->setActivity($activity);
                return $controller;
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
