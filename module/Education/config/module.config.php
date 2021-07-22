<?php

use Education\Controller\{
    AdminController,
    EducationController,
};
use Education\Controller\Factory\{
    AdminControllerFactory,
    EducationControllerFactory,
};

return [
    'router' => [
        'routes' => [
            'education' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/education',
                    'defaults' => [
                        'controller' => EducationController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'course' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/course[/:code]',
                            'constraints' => [
                                'code' => '[a-zA-Z0-9]{5,6}',
                            ],
                            'defaults' => [
                                'action' => 'course',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'download' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/download/:id',
                                    'constraints' => [
                                        'id' => '[0-9]*',
                                    ],
                                    'defaults' => [
                                        'action' => 'download',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'default' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'admin_education' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/admin/education',
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
                    'add_course' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/add/course',
                            'defaults' => [
                                'action' => 'addCourse',
                            ],
                        ],
                    ],
                    'bulk_upload_exam' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/bulk/exam',
                            'defaults' => [
                                'action' => 'bulkExam',
                            ],
                        ],
                    ],
                    'bulk_upload_summary' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/bulk/summary',
                            'defaults' => [
                                'action' => 'bulkSummary',
                            ],
                        ],
                    ],
                    'bulk_edit_exam' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/edit/exam',
                            'defaults' => [
                                'action' => 'editExam',
                            ],
                        ],
                    ],
                    'bulk_edit_summary' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/edit/summary',
                            'defaults' => [
                                'action' => 'editSummary',
                            ],
                        ],
                    ],
                    'delete_temp' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:type/:filename/delete',
                            'constraints' => [
                                'type' => 'exam|summary',
                            ],
                            'defaults' => [
                                'action' => 'deleteTemp',
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
            EducationController::class => EducationControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'education' => __DIR__ . '/../view/',
        ],
    ],
    'doctrine' => [
        'driver' => [
            'education_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Model/'],
            ],
            'orm_default' => [
                'drivers' => [
                    'Education\Model' => 'education_entities',
                ],
            ],
        ],
    ],
];
