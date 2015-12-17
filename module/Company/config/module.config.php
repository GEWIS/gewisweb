<?php

return [
    'router' => [
        'routes' => [
            'company' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/company',
                    'priority' => 2,
                    'defaults' => [
                        '__NAMESPACE__' => 'Company\Controller',
                        'controller' => 'Company',
                        'action' => 'list', // index is reserved for some magical frontpage for the company module, but since it is not yet implemented, a company list will be presented.
                        'actionArgument' => '',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'jobList' => [
                        'priority' => 3,
                        'type' => 'literal',
                        'options' => [
                            'route' => '/jobs',
                            'defaults' => [
                                '__NAMESPACE__' => 'Company\Controller',
                                'controller' => 'Company',
                                'action' => 'jobList',
                                'actionArgument' => '',
                            ],
                        ],
                    ],
                    'list' => [
                        'priority' => 3,
                        'type' => 'literal',
                        'options' => [
                            'route' => '/list',
                            'defaults' => [
                                'controller' => 'Company\Controller\Company',
                                'action' => 'list',
                                'slugCompanyName' => '',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                    'companyItem' => [
                        'priority' => 2,
                        'type' => 'segment',
                        'options' => [
                            // url will be company/<slugCompanyName>/jobs/<slugJobName>/<action>
                            // slugjobname and slugcompanyname will be in database, and can be set from the admin panel
                            // company/apple should give page of apple
                            // company/apple/jobs should be list of jobs of apple
                            // company/apple/jobs/ceo should be the page of ceo job
                            // company should give frontpage of company part
                            // company/list should give a list of companies
                            // company/index should give the frontpage
                            'route' => '/:slugCompanyName',
                            'constraints' => [
                                'slugCompanyName' => '[a-zA-Z0-9_-]*',
                            ],
                        ),
                        'may_terminate' => true,
                        'child_routes' => [
                            'joblist' => [
                                'type' => 'literal',
                                'options' => [
                                    'route' => '/jobs',
                                    'defaults' => [
                                        'controller' => 'Company\Controller\Company',
                                        'action' => 'jobs',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'job_item' => [
                                        'type' => 'segment',
                                        'options' => [
                                            'route' => '[/:slugJobName]',
                                            'constraints' => [
                                                'slugJobName' => '[a-zA-Z0-9_-]*',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ),
            'admin_company' => [
                'priority' => 3,
                'type' => 'Literal',
                'options' => [
                    'route' => '/company/admin/company',
                    'defaults' => [
                        '__NAMESPACE__' => 'Company\Controller',
                        'controller' => 'Admin',
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'deleteCompany' => [
                        'priority' => 3,
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/delete/[:slugCompanyName]',
                            'defaults' => [
                                'action' => 'deleteCompany',
                            ),
                            'constraints' => [
                                'slugCompanyName' => '[a-zA-Z0-9_-]*',
                            ],

                        ],
                        'may_terminate' => true,
                    ],
                    'editCompany' => [
                        'priority' => 3,
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/edit/[:slugCompanyName]',
                            'defaults' => [
                                'action' => 'editCompany',
                            ],
                            'constraints' => [
                                'slugCompanyName' => '[a-zA-Z0-9_-]*',
                            ],

                        ],
                        'may_terminate' => true,

                        'child_routes' => [
                            'editPackage' => [
                                'type' => 'segment',
                                'options' => [
                                    'route' => '/package/:packageID',
                                    'defaults' => [
                                        'action' => 'editPackage',
                                    ],
                                    'constraints' => [
                                        'packageID' => '[a-zA-Z0-9_-]*',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'addJob' => [
                                        'type' => 'segment',
                                        'options' => [
                                            'route' => '/addJob',
                                            'defaults' => [
                                                'action' => 'addJob',
                                            ],
                                        ],
                                        'may_terminate' => true,
                                    ],
                                    'deletePackage' => [
                                        'type' => 'segment',
                                        'options' => [
                                            'route' => '/delete',
                                            'defaults' => [
                                                'action' => 'deletePackage',
                                            ),
                                        ),
                                        'may_terminate' => true,
                                    ],
                                    'editJob' => [
                                        'type' => 'segment',
                                        'options' => [
                                            'route' => '/job/:jobName',
                                            'defaults' => [
                                                'action' => 'editJob',
                                            ),
                                            'constraints' => [
                                                'jobName' => '[a-zA-Z0-9_-]*',
                                            ),
                                            'may_terminate' => true,
                                        ),
                                    ),
                                ),
                            ),
                            'addPackage' => [
                                'type' => 'segment',
                                'options' => [
                                    'route' => '/addPackage',
                                    'defaults' => [
                                        'action' => 'addPackage',
                                    ],
                                    'may_terminate' => true,
                                ],
                            ],
                            'addJob' => [
                                'type' => 'segment',
                                'options' => [
                                    'route' => '/addJob',
                                    'defaults' => [
                                        'action' => 'addJob'
                                    ],
                                    'may_terminate' => true,
                                ],
                            ],
                            'editJob' => [
                                'type' => 'segment',
                                'options' => [
                                    'route' => '/job/:jobName',
                                    'defaults' => [
                                        'action' => 'editJob',
                                    ],
                                    'constraints' => [
                                        'jobName'     => '[a-zA-Z0-9_-]*',
                                    ],
                                    'may_terminate' => true,
                                ],
                            ],
                        ],
                    ],
                    'default' => [
                        'priority' => 2,
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:action[/:slugCompanyName[/:slugJobName]]]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
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
            'Company\Controller\Admin' => 'Company\Controller\AdminController',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'company' => __DIR__.'/../view/',
        ],
    ],
    'doctrine' => [
        'driver' => [
            'company_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__.'/../src/Company/Model/'),
            ],
            'orm_default' => [
                'drivers' => [
                    'Company\Model' => 'company_entities',
                ],
            ],
        ],
    ],
];
