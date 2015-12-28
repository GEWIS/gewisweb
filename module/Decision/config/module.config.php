<?php
return [
    'router' => [
        'routes' => [
            'decision' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/decision',
                    'defaults' => [
                        '__NAMESPACE__' => 'Decision\Controller',
                        'controller' => 'Decision',
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                    'meeting' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:type/:number',
                            'constraints' => [
                                'type' => 'BV|AV|VV|Virt',
                                'number' => '[0-9]+'
                            ],
                            'defaults' => [
                                'action' => 'view'
                            ]
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'notes' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/notes',
                                    'defaults' => [
                                        'action' => 'notes',
                                    ],
                                ],
                            ],
                        ]
                    ],
                    'document' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/document/:id',
                            'constraints' => [
                                'id' => '[0-9]+'
                            ],
                            'defaults' => [
                                'action' => 'document',
                            ],
                        ],
                    ],
                ],
                'priority' => 100
            ],
            'admin_decision' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/admin/decision',
                    'defaults' => [
                        '__NAMESPACE__' => 'Decision\Controller',
                        'controller' => 'Admin',
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                    'notes' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/notes',
                            'defaults' => [
                                'action' => 'notes'
                            ]
                        ]
                    ],
                    'document' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/document',
                            'defaults' => [
                                'action' => 'document'
                            ]
                        ]
                    ],
                ],
                'priority' => 100
            ],
            'organ' => [
                'type' => 'Literal',
                'options' => [
                    'route'    => '/organ',
                    'defaults' => [
                        '__NAMESPACE__' => 'Decision\Controller',
                        'controller'    => 'Organ',
                        'action'        => 'index'
                    ]
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'show' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/show/:organ',
                            'constraints' => [
                                'organ' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'show'
                            ]
                        ],
                    ],
                ],
                'priority' => 100
            ],
            'member' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/member',
                    'defaults' => [
                        '__NAMESPACE__' => 'Decision\Controller',
                        'controller' => 'Member',
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'search' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/search',
                            'defaults' => [
                                'action' => 'search',
                            ],
                        ],
                    ],
                    'birthdays' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/birthdays',
                            'defaults' => [
                                'action' => 'birthdays'
                            ]
                        ]
                    ],
                    'dreamspark' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/dreamspark',
                            'defaults' => [
                                'action' => 'dreamspark'
                            ]
                        ]
                    ],
                    'view' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:lidnr',
                            'constraints' => [
                                'lidnr' => '[0-9]+'
                            ],
                            'defaults' => [
                                'action' => 'view'
                            ]
                        ]
                    ]
                ],
                'priority' => 100
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Decision\Controller\Decision' => 'Decision\Controller\DecisionController',
            'Decision\Controller\Organ' => 'Decision\Controller\OrganController',
            'Decision\Controller\Admin' => 'Decision\Controller\AdminController',
            'Decision\Controller\Member' => 'Decision\Controller\MemberController'
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            'decision' => __DIR__ . '/../view/'
        ]
    ],
    'doctrine' => [
        'driver' => [
            'decision_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Decision/Model/']
            ],
            'orm_default' => [
                'drivers' => [
                    'Decision\Model' => 'decision_entities'
                ]
            ]
        ]
    ]
];
