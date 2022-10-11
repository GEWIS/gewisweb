<?php

namespace Decision;

use Decision\Controller\{
    AdminController,
    DecisionController,
    MemberController,
    OrganAdminController,
    OrganController,
};
use Decision\Controller\Factory\{
    AdminControllerFactory,
    DecisionControllerFactory,
    MemberControllerFactory,
    OrganAdminControllerFactory,
    OrganControllerFactory,
};
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Laminas\Http\Request;
use Laminas\Router\Http\{
    Literal,
    Method,
    Regex,
    Segment,
};

return [
    'router' => [
        'routes' => [
            'decision' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/decision',
                    'defaults' => [
                        'controller' => DecisionController::class,
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
                    'search' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/search',
                            'defaults' => [
                                'action' => 'search',
                            ],
                        ],
                    ],
                    'meeting' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:type/:number',
                            'constraints' => [
                                'type' => 'BV|AV|VV|Virt',
                                'number' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'view',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'minutes' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/minutes',
                                    'defaults' => [
                                        'action' => 'minutes',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'document' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/document/:id',
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'document',
                            ],
                        ],
                    ],
                    'authorizations' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/authorizations',
                            'defaults' => [
                                'action' => 'authorizations',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'revoke' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/revoke',
                                    'defaults' => [
                                        'action' => 'revokeAuthorization',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'files' => [
                        'type' => Regex::class,
                        'options' => [
                            'regex' => '/files(?<path>' . (new Module())->getServiceConfig()['filebrowser_valid_file'] . ')',
                            'defaults' => [
                                'action' => 'files',
                            ],
                            'spec' => '/files/%path%',
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'admin_decision' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/decision',
                    'defaults' => [
                        'controller' => AdminController::class,
                    ],
                ],
                'may_terminate' => false,
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
                    'minutes' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/minutes',
                            'defaults' => [
                                'action' => 'minutes',
                            ],
                        ],
                    ],
                    'document' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/document[/:type/:number]',
                            'constraints' => [
                                'type' => 'BV|AV|VV|Virt',
                                'number' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'document',
                            ],
                        ],
                    ],
                    'delete_document' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/document/delete',
                        ],
                        'may_terminate' => false,
                        'child_routes' => [
                            'post' => [
                                'type' => Method::class,
                                'options' => [
                                    'verb' => Request::METHOD_POST,
                                    'route' => '/document/delete',
                                    'defaults' => [
                                        'action' => 'deleteDocument',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'position_document' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/document/position',
                        ],
                        'may_terminate' => false,
                        'child_routes' => [
                            'post' => [
                                'type' => Method::class,
                                'options' => [
                                    'verb' => Request::METHOD_POST,
                                    'defaults' => [
                                        'action' => 'changePositionDocument',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'authorizations' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/authorizations[/:number]',
                            'constraints' => [
                                'number' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'authorizations',
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'organ' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/organ',
                    'defaults' => [
                        'controller' => OrganController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'show' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/show/:organ',
                            'constraints' => [
                                'organ' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'show',
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'member' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/member',
                    'defaults' => [
                        'controller' => MemberController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'search' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/search',
                            'defaults' => [
                                'action' => 'search',
                            ],
                        ],
                    ],
                    'canauth' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/canauth',
                            'defaults' => [
                                'action' => 'canAuthorize',
                            ],
                        ],
                    ],
                    'birthdays' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/birthdays',
                            'defaults' => [
                                'action' => 'birthdays',
                            ],
                        ],
                    ],
                    'view' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:lidnr',
                            'constraints' => [
                                'lidnr' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'view',
                            ],
                        ],
                    ],
                    'self' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/self',
                            'defaults' => [
                                'action' => 'self',
                            ],
                        ],
                    ],
                    'regulations' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/regulations/:regulation',
                            'constraints' => [
                                'regulation' => '[a-zA-Z_-]+',
                            ],
                            'defaults' => [
                                'action' => 'downloadRegulation',
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'admin_organ' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/organ',
                    'defaults' => [
                        'controller' => OrganAdminController::class,
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
                    'edit' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:organ_id]/edit',
                            'constraints' => [
                                'organ_id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'edit',
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
            AdminController::class => AdminControllerFactory::class,
            DecisionController::class => DecisionControllerFactory::class,
            MemberController::class => MemberControllerFactory::class,
            OrganAdminController::class => OrganAdminControllerFactory::class,
            OrganController::class => OrganControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'decision' => __DIR__ . '/../view/',
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
