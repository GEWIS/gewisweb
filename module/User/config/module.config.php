<?php

namespace User;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Laminas\Router\Http\{
    Literal,
    Segment,
};
use User\Controller\{
    ApiAdminController,
    ApiAuthenticationController,
    ApiController,
    UserController,
};
use User\Controller\Factory\{
    ApiAdminControllerFactory,
    ApiAuthenticationControllerFactory,
    ApiControllerFactory,
    UserControllerFactory,
};

return [
    'router' => [
        'routes' => [
            'user' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/user',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                    'login' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/login',
                        ],
                    ],
                    'logout' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/logout',
                            'defaults' => [
                                'action' => 'logout',
                            ],
                        ],
                    ],
                    'activate_reset' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/reset/:code',
                            'constraints' => [
                                'code' => '[a-zA-Z0-9]*',
                            ],
                            'defaults' => [
                                'code' => '',
                                'action' => 'activateReset',
                            ],
                        ],
                    ],
                    'activate' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/activate/:code',
                            'constraints' => [
                                'code' => '[a-zA-Z0-9]*',
                            ],
                            'defaults' => [
                                'code' => '',
                                'action' => 'activate',
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'user_admin' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/user',
                ],
                'may_terminate' => false,
                'child_routes' => [
                    'api' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/api',
                            'defaults' => [
                                'controller' => ApiAdminController::class,
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'remove' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/remove/:id',
                                    'constraints' => [
                                        'id' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'remove',
                                    ],
                                ],
                            ],
                            'default' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/:action',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'user_token' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/token/:appId',
                    'defaults' => [
                        'controller' => ApiAuthenticationController::class,
                        'action' => 'token',
                    ],
                ],
                'priority' => 100,
            ],
            'validate_login' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/validateLogin',
                    'defaults' => [
                        'controller' => ApiController::class,
                        'action' => 'validate',
                    ],
                ],
                'priority' => 100,
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            ApiAdminController::class => ApiAdminControllerFactory::class,
            ApiAuthenticationController::class => ApiAuthenticationControllerFactory::class,
            ApiController::class => ApiControllerFactory::class,
            UserController::class => UserControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'user' => __DIR__ . '/../view/',
        ],
        'template_map' => [
            'user/login' => __DIR__ . '/../view/partial/login.phtml',
            'user_token/redirect' => __DIR__ . '/../view/user/api-authentication/redirect.phtml',
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
