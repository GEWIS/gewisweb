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
                    'touch' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/touch',
                            'defaults' => [
                                'action' => 'touch'
                            ]
                        ]
                    ],
                ],
                'priority' => 100
            ],
            'organizer_activity' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/activity/organizer/',
                    'defaults' => [
                        '__NAMESPACE__' => 'Activity\Controller',
                        'controller' => 'organizer',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'email' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => ':id/email',
                            'defaults' => [
                                'controller' => 'organizer',
                                'action' => 'email',
                            ]
                        ]
                    ],
                    'export' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => ':id/export',
                            'defaults' => [
                                'controller' => 'organizer',
                                'action' => 'export',
                            ]
                        ]
                    ],
                    'exportpdf' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => ':id/export/pdf',
                            'defaults' => [
                                'controller' => 'organizer',
                                'action' => 'exportpdf',
                            ]
                        ]
                    ],
                    'update' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => ':id/update',
                            'defaults' => [
                                'controller' => 'organizer',
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
            'admin_activity' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/admin/activity',
                    'defaults' => [
                        '__NAMESPACE__' => 'Activity\Controller',
                        'controller' => 'admin',
                        'action' => 'queue'
                    ]
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'queue' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/queue',
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'queue'
                            ]
                        ]
                    ],
                    'queue_unapproved' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/queue/unapproved[/:page]',
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'queueUnapproved'
                            ]
                        ]
                    ],
                    'queue_approved' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/queue/approved[/:page]',
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'queueApproved'
                            ]
                        ]
                    ],
                    'queue_disapproved' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/queue/disapproved[/:page]',
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'queueDisapproved'
                            ]
                        ]
                    ],
                    'view' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/view/[:id]',
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'view'
                            ]
                        ]
                    ],
                    'proposal' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/proposal/[:id]',
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'viewProposal'
                            ]
                        ]
                    ],
                    'apply_proposal' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/proposal/[:id]/apply',
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'applyProposal'
                            ]
                        ]
                    ],
                    'revoke_proposal' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/proposal/[:id]/revoke',
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'revokeProposal'
                            ]
                        ]
                    ],
                    'approve' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/approve/[:id]',
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'approve'
                            ]
                        ]
                    ],
                    'disapprove' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/disapprove/[:id]',
                            'defaults' => [
                                'controller' => 'admin',
                                'action' => 'disapprove'
                            ]
                        ]
                    ],
                    'reset' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/reset/[:id]',
                            'defaults' => [
                                'controller' => 'admin',
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
            'Activity\Controller\Admin' => 'Activity\Controller\AdminController',
            'Activity\Controller\Api' => 'Activity\Controller\ApiController',
            'Activity\Controller\Organizer' => 'Activity\Controller\OrganizerController',
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
