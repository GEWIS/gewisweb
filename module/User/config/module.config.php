<?php

declare(strict_types=1);

namespace User;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use User\Command\DeleteOldLoginAttempts;
use User\Controller\ApiAdminController;
use User\Controller\ApiAuthenticationController;
use User\Controller\Factory\ApiAdminControllerFactory;
use User\Controller\Factory\ApiAuthenticationControllerFactory;
use User\Controller\Factory\UserAdminControllerFactory;
use User\Controller\Factory\UserControllerFactory;
use User\Controller\UserAdminController;
use User\Controller\UserController;
use User\Listener\Authentication;

return [
    'router' => [
        'routes' => [
            'user' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/user',
                    'defaults' => [
                        'controller' => UserController::class,
                    ],
                ],
                'may_terminate' => false,
                'child_routes' => [
                    'activate' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/activate[/:user_type/:code]',
                            'constraints' => [
                                'code' => '[a-zA-Z0-9]+',
                                'user_type' => '(company|member)',
                            ],
                            'defaults' => [
                                'action' => 'activate',
                                'user_type' => 'member',
                            ],
                        ],
                    ],
                    // The `register` endpoint only exists to handle cases where users click links in old e-mails.
                    // TODO: remove after 1 January 2025.
                    'register' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/register',
                            'defaults' => [
                                'action' => 'activate',
                                'user_type' => 'member',
                            ],
                        ],
                    ],
                    'login' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/login[/:user_type]',
                            'constraints' => [
                                'user_type' => '(company|member)',
                            ],
                            'defaults' => [
                                'action' => 'login',
                                'user_type' => 'member',
                            ],
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
                    'password' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/password',
                        ],
                        'may_terminate' => false,
                        'child_routes' => [
                            'change' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/change[/:user_type]',
                                    'constraints' => [
                                        'user_type' => '(company|member)',
                                    ],
                                    'defaults' => [
                                        'action' => 'changePassword',
                                        'user_type' => 'member',
                                    ],
                                ],
                            ],
                            'reset' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/reset[/:user_type]',
                                    'constraints' => [
                                        'user_type' => '(company|member)',
                                    ],
                                    'defaults' => [
                                        'action' => 'resetPassword',
                                        'user_type' => 'member',
                                    ],
                                ],
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
                    'defaults' => [
                        'auth_type' => Authentication::AUTH_USER,
                    ],
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
                    'members' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/members',
                            'defaults' => [
                                'controller' => UserAdminController::class,
                                'action' => 'index',
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
                        'auth_type' => Authentication::AUTH_USER,
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
            UserAdminController::class => UserAdminControllerFactory::class,
            UserController::class => UserControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'user' => __DIR__ . '/../view/',
        ],
        'template_map' => [
            'user_token/redirect' => __DIR__ . '/../view/user/api-authentication/redirect.phtml',
        ],
    ],
    'laminas-cli' => [
        'commands' => [
            'user:gdpr:delete-old-loginattempts' => DeleteOldLoginAttempts::class,
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
