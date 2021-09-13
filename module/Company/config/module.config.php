<?php

namespace Company;

use Application\Extensions\Doctrine\AttributeDriver;
use Application\View\Helper\Truncate;
use Company\Controller\{
    AdminController,
    CompanyController,
};
use Company\Controller\Factory\{
    AdminControllerFactory,
    CompanyControllerFactory,
};
use Laminas\Router\Http\{
    Literal,
    Segment,
};

return [
    'router' => [
        'routes' => [
            'company' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/career',
                    'priority' => 2,
                    'defaults' => [
                        'controller' => CompanyController::class,
                        'action' => 'list', // index is reserved for some magical frontpage for the company module, but since it is not yet implemented, a company list will be presented.
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'jobList' => [
                        'priority' => 3,
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:category',
                            'constraints' => [
                                'category' => '[a-zA-Z0-9_\-\.]*',
                            ],
                            'defaults' => [
                                'action' => 'jobList',
                            ],
                        ],
                    ],
                    'spotlight' => [
                        'priority' => 3,
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/spotlight',
                            'defaults' => [
                                'action' => 'spotlight',
                            ],
                        ],
                    ],
                    'list' => [
                        'priority' => 3,
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/list',
                            'defaults' => [
                                'action' => 'list',
                                'companySlugName' => '',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                    'companyItem' => [
                        'priority' => 2,
                        'type' => Segment::class,
                        'options' => [
                            'defaults' => [
                                'action' => 'show',
                            ],
                            // url will be company/<companySlugName>/jobs/<jobSlugName>/<action>
                            // jobSlugName and companySlugName will be in database, and can be set from the admin panel
                            // company/apple should give page of apple
                            // company/apple/jobs should be list of jobs of apple
                            // company/apple/jobs/ceo should be the page of ceo job
                            // company should give frontpage of company part
                            // company/list should give a list of companies
                            // company/index should give the frontpage
                            'route' => '/company/:companySlugName',
                            'constraints' => [
                                'companySlugName' => '[a-zA-Z0-9_\-\.]*',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'joblist' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/:category',
                                    'defaults' => [
                                        'action' => 'jobList',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'job_item' => [
                                        'type' => Segment::class,
                                        'options' => [
                                            'route' => '/:jobSlugName',
                                            'constraints' => [
                                                'jobSlugName' => '[a-zA-Z0-9_-]*',
                                            ],
                                            'defaults' => [
                                                'action' => 'jobs',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'company_admin' => [
                'priority' => 1000,
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/career',
                    'defaults' => [
                        'controller' => AdminController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'company' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/company',
                        ],
                        'may_terminate' => false,
                        'child_routes' => [
                            'add' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/add',
                                    'defaults' => [
                                        'action' => 'addCompany',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/delete/:companySlugName',
                                    'defaults' => [
                                        'action' => 'deleteCompany',
                                    ],
                                    'constraints' => [
                                        'companySlugName' => '[a-zA-Z0-9_\-\.]*',
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/edit/:companySlugName',
                                    'defaults' => [
                                        'action' => 'editCompany',
                                    ],
                                    'constraints' => [
                                        'companySlugName' => '[a-zA-Z0-9_\-\.]*',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'package' => [
                                        'type' => Literal::class,
                                        'options' => [
                                            'route' => '/package',
                                        ],
                                        'may_terminate' => false,
                                        'child_routes' => [
                                            'add' => [
                                                'type' => Segment::class,
                                                'options' => [
                                                    'route' => '/add/:type',
                                                    'defaults' => [
                                                        'action' => 'addPackage',
                                                    ],
                                                    'constraints' => [
                                                        'type' => '(banner|featured|job)',
                                                    ],
                                                ],
                                            ],
                                            'delete' => [
                                                'type' => Segment::class,
                                                'options' => [
                                                    'route' => '/delete/:packageId',
                                                    'defaults' => [
                                                        'action' => 'deletePackage',
                                                    ],
                                                    'constraints' => [
                                                        'packageId' => '[0-9]*',
                                                    ],
                                                ],
                                            ],
                                            'edit' => [
                                                'type' => Segment::class,
                                                'options' => [
                                                    'route' => '/edit/:packageId',
                                                    'defaults' => [
                                                        'action' => 'editPackage',
                                                    ],
                                                    'constraints' => [
                                                        'packageId' => '[0-9]*',
                                                    ],
                                                ],
                                                'may_terminate' => true,
                                                'child_routes' => [
                                                    'job' => [
                                                        'type' => Literal::class,
                                                        'options' => [
                                                            'route' => '/job',
                                                        ],
                                                        'may_terminate' => false,
                                                        'child_routes' => [
                                                            'add' => [
                                                                'type' => Segment::class,
                                                                'options' => [
                                                                    'route' => '/add',
                                                                    'defaults' => [
                                                                        'action' => 'addJob',
                                                                    ],
                                                                ],
                                                            ],
                                                            'delete' => [
                                                                'type' => Segment::class,
                                                                'options' => [
                                                                    'route' => '/delete/:jobId',
                                                                    'defaults' => [
                                                                        'action' => 'deleteJob',
                                                                    ],
                                                                    'constraints' => [
                                                                        'jobId' => '[0-9]*',
                                                                    ],
                                                                ],
                                                            ],
                                                            'edit' => [
                                                                'type' => Segment::class,
                                                                'options' => [
                                                                    'route' => '/edit/:jobId',
                                                                    'defaults' => [
                                                                        'action' => 'editJob',
                                                                    ],
                                                                    'constraints' => [
                                                                        'jobId' => '[0-9]*',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'category' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/category',
                        ],
                        'may_terminate' => false,
                        'child_routes' => [
                            'add' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/add',
                                    'defaults' => [
                                        'action' => 'addCategory',
                                    ],
                                ],
                            ],
                            'edit' => [
                                'priority' => 3,
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/edit/:jobCategoryId',
                                    'defaults' => [
                                        'action' => 'editCategory',
                                    ],
                                    'constraints' => [
                                        'jobCategoryId' => '[0-9]*',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'label' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/label',
                        ],
                        'may_terminate' => false,
                        'child_routes' => [
                            'add' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/add',
                                    'defaults' => [
                                        'action' => 'addLabel',
                                    ],
                                ],
                            ],
                            'edit' => [
                                'priority' => 3,
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/edit/:jobLabelId',
                                    'defaults' => [
                                        'action' => 'editLabel',
                                    ],
                                    'constraints' => [
                                        'jobLabelId' => '[0-9]*',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            AdminController::class => AdminControllerFactory::class,
            CompanyController::class => CompanyControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'company' => __DIR__ . '/../view/',
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
    'view_helpers' => [
        'factories' => [
            'truncate' => function () {
                return new Truncate();
            },
        ],
    ],
];
