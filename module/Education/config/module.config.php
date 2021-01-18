<?php
return [
    'router' => [
        'routes' => [
            'education' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/education',
                    'defaults' => [
                        '__NAMESPACE__' => 'Education\Controller',
                        'controller'    => 'Education',
                        'action'        => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'course' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/course[/:code]',
                            'constraints' => [
                                'code' => '[a-zA-Z0-9]{5,6}'
                            ],
                            'defaults' => [
                                'action' => 'course'
                            ]
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
                                        'action' => 'download'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'default' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '[/:action]',
                            'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ]
                ],
                'priority' => 100
            ],
            'admin_education' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/admin/education',
                    'defaults' => [
                        '__NAMESPACE__' => 'Education\Controller',
                        'controller'    => 'Admin',
                        'action'        => 'index'
                    ]
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
                    'add_course' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/add/course',
                            'defaults' => [
                                'action' => 'addCourse'
                            ]
                        ]
                    ],
                    'bulk_upload_exam' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/bulk/exam',
                            'defaults' => [
                                'action' => 'bulkExam'
                            ]
                        ]
                    ],
                    'bulk_upload_summary' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/bulk/summary',
                            'defaults' => [
                                'action' => 'bulkSummary'
                            ]
                        ]
                    ],
                    'bulk_edit_exam' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/edit/exam',
                            'defaults' => [
                                'action' => 'editExam'
                            ]
                        ]
                    ],
                    'bulk_edit_summary' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/edit/summary',
                            'defaults' => [
                                'action' => 'editSummary'
                            ]
                        ]
                    ],
                    'delete_temp' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:type/:filename/delete',
                            'constraints' => [
                                'type' => 'exam|summary',
                            ],
                            'defaults' => [
                                'action' => 'deleteTemp'
                            ]
                        ],
                    ]
                ],
                'priority' => 100
            ]
        ]
    ],
    'controllers' => [
        'invokables' => [
            'Education\Controller\Education' => 'Education\Controller\EducationController',
            'Education\Controller\Admin' => 'Education\Controller\AdminController',
            'Education\Controller\Oase' => 'Education\Controller\OaseController'
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            'education' => __DIR__ . '/../view/'
        ]
    ],
    'doctrine' => [
        'driver' => [
            'education_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Education/Model/']
            ],
            'orm_default' => [
                'drivers' => [
                    'Education\Model' => 'education_entities'
                ]
            ]
        ]
    ],
    // console routes
    'console' => [
        'router' => [
            'routes' => [
                'oase' => [
                    'options' => [
                        'route' => 'oase update',
                        'defaults' => [
                            'controller' => 'Education\Controller\Oase',
                            'action' => 'index'
                        ]
                    ]
                ],
                'oase-show-studies' => [
                    'options' => [
                        'route' => 'oase show studies',
                        'defaults' => [
                            'controller' => 'Education\Controller\Oase',
                            'action' => 'studies'
                        ]
                    ]
                ],
                'oase-show-course' => [
                    'options' => [
                        'route' => 'oase show course <code>',
                        'defaults' => [
                            'controller' => 'Education\Controller\Oase',
                            'action' => 'course'
                        ]
                    ]
                ]

            ]
        ]
    ]
];
