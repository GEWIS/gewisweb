<?php
use User\Controller\ApiAuthenticationController;
use User\Controller\Factory\ApiAuthenticationControllerFactory;

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
                    'logout' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/logout',
                            'defaults' => [
                                'action' => 'logout'
                            ]
                        ],
                    ],
                    'pinlogin' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/pinlogin',
                            'defaults' => [
                                'action' => 'pinLogin',
                            ],
                        ],
                    ],
                    'activate_reset' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/reset/:code',
                            'constraints' => [
                                'code' => '[a-zA-Z0-9]*'
                            ],
                            'defaults' => [
                                'code'   => '',
                                'action' => 'activateReset'
                            ]
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
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/api',
                            'defaults' => [
                                'controller' => 'ApiAdmin',
                                'action'     => 'index'
                            ]
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'remove' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route'    => '/remove/:id',
                                    'constraints' => [
                                        'id' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'remove'
                                    ]
                                ]
                            ],
                            'default' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route'    => '/:action',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'priority' => 100
            ],
            'user_token' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/token/:appId',
                    'defaults' => [
                        'controller' => '\User\Controller\ApiAuthenticationController',
                        'action' => 'token',
                    ]
                ],
                'priority' => 100
            ]
        ],
    ],
    'controllers' => [
        'invokables' => [
            'User\Controller\User' => 'User\Controller\UserController',
            'User\Controller\ApiAdmin' => 'User\Controller\ApiAdminController',
        ],
        'factories' => [
            'ApiAuthenticationController' => 'ApiAuthenticationControllerFactory',
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            'user' => __DIR__ . '/../view/'
        ],
        'template_map' => [
            'user/login'       => __DIR__ . '/../view/partial/login.phtml',
        ],
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
