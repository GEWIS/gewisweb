<?php
return [
    'router' => [
        'routes' => [
            'company' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/company',
                    'defaults' => [
                        '__NAMESPACE__' => 'Company\Controller',
                        'controller'    => 'Company',
                        'action'        => 'list', // index is reserved for frontpage, but since it is not yet implemented, a company list will be presented.
                        'actionArgument'=> '',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'list' => [
                        'priority' => 3,
                        'type' => 'literal',
                        'options' => [
                            'route' => '/list',
                            'defaults' => [
                                'controller' => 'Company\Controller\Company',
                                'action' => 'list',
                                'asciiCompanyName' => '',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                    'companyItem' => [
                        'priority' => 2,
                        'type'    => 'segment',
                        'options' => [
                            // url will be company/<asciiCompanyName>/jobs/<asciiJobName>/<action>
                            // asciijobname and asciicompanyname will be in database, and can be set from the admin panel
                            // company/apple should give page of apple
                            // company/apple/jobs should be list of jobs of apple
                            // company/apple/jobs/ceo should be the page of ceo job
                            // company should give frontpage of company part
                            // company/list should give a list of companies
                            // company/index should give the frontpage
                            'route'    => '/:asciiCompanyName',
                            'constraints' => [
                                'asciiCompanyName'     => '[a-zA-Z0-9_-]*',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'joblist' => [
                                'type' => 'literal',
                                'options' => [
                                    'route' => '/jobs',
                                    'defaults' => [
                                        'controller' => 'Company\Controller\Company',
                                        'action' => 'jobs'
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'job_item' => [
                                        'type' => 'segment',
                                        'options' => [
                                            'route' => '[/:asciiJobName]',
                                            'constraints' => [
                                                'asciiJobName'     => '[a-zA-Z0-9_-]*',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'priority' => 100
            ),
            'admin_company' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/admin/company',
                    'defaults' => [
                        '__NAMESPACE__' => 'Company\Controller',
                        'controller'    => 'Admin',
                        'action'        => 'index'
                    )
                ),
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '[/:action[/:asciiCompanyName]]',
                            'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Company\Controller\Company' => 'Company\Controller\CompanyController',
            'Company\Controller\Admin' => 'Company\Controller\AdminController'
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            'company' => __DIR__ . '/../view/'
        ]
    ],
    'doctrine' => [
        'driver' => [
            'company_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Company/Model/']
            ],
            'orm_default' => [
                'drivers' => [
                    'Company\Model' => 'company_entities'
                ]
            ]
        ]
    ]
];
