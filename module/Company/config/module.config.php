<?php
return array(
    'router' => array(
        'routes' => array(
            'company' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/company',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Company\Controller',
                        'controller'    => 'Company',
                        'action'        => 'list', // index is reserved for some magical frontpage for the company module, but since it is not yet implemented, a company list will be presented.
                        'actionArgument'=> '',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'list' => array(
                        'priority' => 3,
                        'type' => 'literal',
                        'options' => array(
                            'route' => '/list',
                            'defaults' => array(
                                'controller' => 'Company\Controller\Company',
                                'action' => 'list',
                                'slugCompanyName' => '',
                            ),
                        ),
                        'may_terminate' => true,
                    ),
                    'companyItem' => array(
                        'priority' => 2,
                        'type'    => 'segment',
                        'options' => array(
                            // url will be company/<slugCompanyName>/jobs/<slugJobName>/<action>
                            // slugjobname and slugcompanyname will be in database, and can be set from the admin panel
                            // company/apple should give page of apple
                            // company/apple/jobs should be list of jobs of apple
                            // company/apple/jobs/ceo should be the page of ceo job
                            // company should give frontpage of company part
                            // company/list should give a list of companies
                            // company/index should give the frontpage
                            'route'    => '/:slugCompanyName',
                            'constraints' => array(
                                'slugCompanyName'     => '[a-zA-Z0-9_-]*',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'joblist' => array(
                                'type' => 'literal',
                                'options' => array(
                                    'route' => '/jobs',
                                    'defaults' => array(
                                        'controller' => 'Company\Controller\Company',
                                        'action' => 'jobs'
                                    ),
                                ),
                                'may_terminate' => true,
                                'child_routes' => array(
                                    'job_item' => array(
                                        'type' => 'segment',
                                        'options' => array(
                                            'route' => '[/:slugJobName]',
                                            'constraints' => array(
                                                'slugJobName'     => '[a-zA-Z0-9_-]*',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'priority' => 100
            ),
            'admin_company' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/admin/company',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Company\Controller',
                        'controller'    => 'Admin',
                        'action'        => 'index'
                    )
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'editCompany' => array(
                        'priority' => 3,
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/edit/[:slugCompanyName]',
                            'defaults' => array(
                                'action' => 'editCompany'
                            ),
                            'constraints' => array(
                                'slugCompanyName'     => '[a-zA-Z0-9_-]*',
                            ),
                             
                        ),
                        'may_terminate' => true,
                        
                        'child_routes' => array(
                            'editPacket' => array(
                                'type' => 'segment',
                                'options' => array(
                                    'route' => '/packet/:packet',
                                    'defaults' => array(
                                        'action' => 'editPacket'
                                    ),
                                    'constraints' => array(
                                        'packet'     => '[a-zA-Z0-9_-]*',
                                    ),
                                    'may_terminate' => true,
                                ),
                            ),
                            'addJob' => array(
                                'type' => 'segment',
                                'options' => array(
                                    'route' => '/addJob',
                                    'defaults' => array(
                                        'action' => 'addJob'
                                    ),
                                    'may_terminate' => true,
                                ),
                            ),
                            'editJob' => array(
                                'type' => 'segment',
                                'options' => array(
                                    'route' => '/job/:jobName',
                                    'defaults' => array(
                                        'action' => 'editJob',
                                    ),
                                    'constraints' => array(
                                        'jobName'     => '[a-zA-Z0-9_-]*',
                                    ),
                                    'may_terminate' => true,
                                ),
                            ),
                        ),
                    ),
                    'default' => array(
                        'priority' => 2,
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '[/:action[/:slugCompanyName[/:slugJobName]]]',
                            'constraints' => array(
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Company\Controller\Company' => 'Company\Controller\CompanyController',
            'Company\Controller\Admin' => 'Company\Controller\AdminController'
        )
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'company' => __DIR__ . '/../view/'
        )
    ),
    'doctrine' => array(
        'driver' => array(
            'company_entities' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/Company/Model/')
            ),
            'orm_default' => array(
                'drivers' => array(
                    'Company\Model' => 'company_entities'
                )
            )
        )
    )
);
