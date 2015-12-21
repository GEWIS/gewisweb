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
            'Activity\Controller\Api' => 'Activity\Controller\ApiController'
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
