<?php
return [
    'router' => [
        'routes' => [
            'user' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/user',
                    'defaults' => [
                        '__NAMESPACE__' => 'User\Controller',
                        'controller'    => 'User',
                        'action'        => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '[/:action]',
                            'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                    'login' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/login',
                        ]
                    ],
                    'activate' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/activate/:code',
                            'constraints' => [
                                'code' => '[a-zA-Z0-9]*'
                            ],
                            'defaults' => [
                                'code'   => '',
                                'action' => 'activate'
                            ]
                        ]
                    ]
                ],
                'priority' => 100
            ],
            'user_admin' => [
                'type'    => 'Literal',
                'options' => [
                    'route' => '/admin/user',
                    'defaults' => [
                        '__NAMESPACE__' => 'User\Controller',
                    ]
                ],
                'may_terminate' => false,
                'child_routes' => [
                    'api' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/api[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller' => 'ApiAdmin',
                                'action'     => 'index'
                            ]
                        ]
                    ]
                ]
            ]
        ],
    ],
    'controllers' => [
        'invokables' => [
            'User\Controller\User' => 'User\Controller\UserController',
            'User\Controller\ApiAdmin' => 'User\Controller\ApiAdminController'
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            'user' => __DIR__ . '/../view/'
        ]
    ],
    'doctrine' => [
        'driver' => [
            'user_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/User/Model/']
            ],
            'orm_default' => [
                'drivers' => [
                    'User\Model' => 'user_entities'
                ]
            ]
        ]
    ]
];
