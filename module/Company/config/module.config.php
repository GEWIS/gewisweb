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
                        'action'        => 'index',
			'actionArgument'=> '',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '[/:action[/:actionArgument]]',
                            'constraints' => array(
                                'action'     	     => '[a-zA-Z][a-zA-Z0-9_-]*',
				'actionArgument'     => '[a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Company\Controller\Company' => 'Company\Controller\CompanyController'
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
