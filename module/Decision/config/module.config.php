<?php
return array(
    'router' => array(
        'routes' => array(
            'decision' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/decision',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Decision\Controller',
                        'controller' => 'Decision',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[/:action]',
                            'constraints' => array(
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                ),
            ),
            'organ' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/organ',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Decision\Controller',
                        'controller'    => 'Organ',
                        'action'        => 'index'
                    )
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'show' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/show/:organ',
                            'constraints' => array(
                                'organ' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'show'
                            )
                        ),
                    ),
                ),
            ),
            'member' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/member',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Decision\Controller',
                        'controller' => 'Member',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'search' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/search[/:name]',
                            'defaults' => array(
                                'action' => 'search',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Decision\Controller\Decision' => 'Decision\Controller\DecisionController',
            'Decision\Controller\Organ' => 'Decision\Controller\OrganController',
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
