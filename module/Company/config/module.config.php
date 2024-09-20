<?php

declare(strict_types=1);

namespace Company;

use Application\View\Helper\Truncate;
use Company\Controller\AdminApprovalController;
use Company\Controller\AdminController;
use Company\Controller\CompanyAccountController;
use Company\Controller\CompanyController;
use Company\Controller\Factory\AdminApprovalControllerFactory;
use Company\Controller\Factory\AdminControllerFactory;
use Company\Controller\Factory\CompanyAccountControllerFactory;
use Company\Controller\Factory\CompanyControllerFactory;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use User\Listener\Authentication;

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
                        // index is reserved for some magical frontpage for the company module, but since it is not yet
                        // implemented, a company list will be presented.
                        'action' => 'list',
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
                                'category' => '[a-zA-Z0-9_\-\.]+',
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
                            'route' => '/company/:companySlugName',
                            'constraints' => [
                                'companySlugName' => '[a-zA-Z0-9_\-\.]+',
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
                                                'jobSlugName' => '[a-zA-Z0-9_-]+',
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
            'company_account' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/company',
                    'defaults' => [
                        'controller' => CompanyAccountController::class,
                        'auth_type' => Authentication::AUTH_COMPANY_USER,
                    ],
                ],
                'may_terminate' => false,
                'child_routes' => [
                    'self' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/self',
                            'defaults' => [
                                'action' => 'self',
                            ],
                        ],
                    ],
                    'jobs_overview' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/jobs',
                            'defaults' => [
                                'action' => 'jobs',
                            ],
                        ],
                    ],
                    'jobs' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/jobs/:packageId',
                            'constraints' => [
                                'packageId' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'jobs',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'add' => [
                                'type' => Literal::class,
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
                                        'jobId' => '[0-9]+',
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
                                        'jobId' => '[0-9]+',
                                    ],
                                ],
                            ],
                            'status' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/status/:jobId',
                                    'defaults' => [
                                        'action' => 'statusJob',
                                    ],
                                    'constraints' => [
                                        'jobId' => '[0-9]+',
                                    ],
                                ],
                            ],
                            'transfer' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/transfer',
                                    'defaults' => [
                                        'action' => 'transferJobs',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'highlights' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/highlights',
                            'defaults' => [
                                'action' => 'highlights',
                            ],
                        ],
                    ],
                    'banner' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/banner',
                            'defaults' => [
                                'action' => 'banner',
                            ],
                        ],
                    ],
                    'settings' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/settings',
                            'defaults' => [
                                'action' => 'settings',
                            ],
                        ],
                    ],
                    'upload' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/upload',
                            'defaults' => [
                                'action' => 'uploadCompanyImage',
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
                                    'upload' => [
                                        'type' => Literal::class,
                                        'options' => [
                                            'route' => '/upload',
                                            'defaults' => [
                                                'action' => 'uploadCompanyImage',
                                            ],
                                        ],
                                    ],
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
                                                        'packageId' => '\d+',
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
                                                        'packageId' => '\d+',
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
                                                                'type' => Literal::class,
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
                                                                        'jobId' => '\d+',
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
                                                                        'jobId' => '\d+',
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
                    'categories' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/categories',
                            'defaults' => [
                                'action' => 'indexCategories',
                            ],
                        ],
                        'may_terminate' => true,
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
                    'labels' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/labels',
                            'defaults' => [
                                'action' => 'indexLabels',
                            ],
                        ],
                        'may_terminate' => true,
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
            'company_admin_approval' => [
                'priority' => 1000,
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/career/approval',
                    'defaults' => [
                        'controller' => AdminApprovalController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'job_approval' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/job/:jobId',
                            'defaults' => [
                                'action' => 'jobApproval',
                            ],
                            'constraints' => [
                                'jobId' => '\d+',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'update' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/:type',
                                    'defaults' => [
                                        'action' => 'changeJobApprovalStatus',
                                    ],
                                    'constraints' => [
                                        'type' => '(approve|disapprove|reset)',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'job_proposal' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/job/proposal/:proposalId',
                            'defaults' => [
                                'action' => 'jobProposal',
                            ],
                            'constraints' => [
                                'proposalId' => '\d+',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'update' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/:type',
                                    'defaults' => [
                                        'action' => 'changeJobProposalStatus',
                                    ],
                                    'constraints' => [
                                        'type' => '(apply|cancel)',
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
            AdminApprovalController::class => AdminApprovalControllerFactory::class,
            CompanyAccountController::class => CompanyAccountControllerFactory::class,
            CompanyController::class => CompanyControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'company' => __DIR__ . '/../view/',
        ],
        'template_map' => [
            'company/admin/index-categories' => __DIR__ . '/../view/company/admin/categories.phtml',
            'company/admin/index-labels' => __DIR__ . '/../view/company/admin/labels.phtml',
            'company/company-account/add-job' => __DIR__ . '/../view/company/admin/add-job.phtml',
            'company/company-account/edit-job' => __DIR__ . '/../view/company/admin/edit-job.phtml',
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
            'truncate' => static function () {
                return new Truncate();
            },
        ],
    ],
];
