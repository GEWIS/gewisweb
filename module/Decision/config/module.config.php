<?php

use Decision\Controller\{
    AdminController,
    DecisionController,
    MemberApiController,
    MemberController,
    OrganAdminController,
    OrganController,
};
use Decision\Controller\Factory\{
    AdminControllerFactory,
    DecisionControllerFactory,
    MemberApiControllerFactory,
    MemberControllerFactory,
    OrganAdminControllerFactory,
    OrganControllerFactory,
};

return [
    'router' => [
        'routes' => [
            'decision' => [
                'type' => 'Literal',
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
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                    'search' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/search',
                            'defaults' => [
                                'action' => 'search',
                            ],
                        ],
                    ],
                    'meeting' => [
                        'type' => 'Segment',
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
                            'notes' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/notes',
                                    'defaults' => [
                                        'action' => 'notes',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'document' => [
                        'type' => 'Segment',
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
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/authorizations',
                            'defaults' => [
                                'action' => 'authorizations',
                            ],
                        ],
                    ],
                    'files' => [
                        'type' => 'Regex',
                        'options' => [
                            'regex' => '/files(?<path>' . $this->getServiceConfig()['filebrowser_valid_file'] . ')',
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
                'type' => 'Literal',
                'options' => [
                    'route' => '/admin/decision',
                    'defaults' => [
                        'controller' => AdminController::class,
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
                                'action' => 'notes',
                            ],
                        ],
                    ],
                    'document' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/document[/:type][/:number]',
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
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/document/delete',
                            'defaults' => [
                                'action' => 'deleteDocument',
                            ],
                        ],
                    ],
                    'position_document' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/document/position',
                            'defaults' => [
                                'action' => 'changePositionDocument',
                            ],
                        ],
                    ],
                    'authorizations' => [
                        'type' => 'Segment',
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
                'type' => 'Literal',
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
                        'type' => 'Segment',
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
                'type' => 'Literal',
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
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/search',
                            'defaults' => [
                                'action' => 'search',
                            ],
                        ],
                    ],
                    'canauth' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/canauth',
                            'defaults' => [
                                'action' => 'canAuthorize',
                            ],
                        ],
                    ],
                    'birthdays' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/birthdays',
                            'defaults' => [
                                'action' => 'birthdays',
                            ],
                        ],
                    ],
                    'dreamspark' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/dreamspark',
                            'defaults' => [
                                'action' => 'dreamspark',
                            ],
                        ],
                    ],
                    'view' => [
                        'type' => 'Segment',
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
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/self',
                            'defaults' => [
                                'action' => 'self',
                            ],
                        ],
                    ],
                    'regulations' => [
                        'type' => 'Segment',
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
            'member_api' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/api/member',
                    'defaults' => [
                        'controller' => MemberApiController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'lidnr' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/lidnr/:lidnr',
                            'defaults' => [
                                'action' => 'lidnr',
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'admin_organ' => [
                'type' => 'Literal',
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
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => 'Segment',
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
            MemberApiController::class => MemberApiControllerFactory::class,
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
            'decision_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Model/'],
            ],
            'orm_default' => [
                'drivers' => [
                    'Decision\Model' => 'decision_entities',
                ],
            ],
        ],
    ],
];
