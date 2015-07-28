<?php
return array(
    'router' => array(
        'routes' => array(
            'decision' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/decision',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Decision\Controller',
                        'controller'    => 'Decision',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '[/:action]',
                            'constraints' => array(
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                ),
            ),
            'member' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/member',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Decision\Controller',
                        'controller'    => 'Member',
                        'action'        => 'index'
                    )
                )
            )
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Decision\Controller\Decision' => 'Decision\Controller\DecisionController',
            'Decision\Controller\Member' => 'Decision\Controller\MemberController'
        )
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'decision' => __DIR__ . '/../view/'
        )
    ),
    'doctrine' => array(
        'driver' => array(
            'decision_entities' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/Decision/Model/')
            ),
            'orm_default' => array(
                'drivers' => array(
                    'Decision\Model' => 'decision_entities'
                )
            )
        )
    )
);
