<?php
return array(
    'router' => array(
        'routes' => array(
            'activity' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/activity',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Activity\Controller',
                        'controller'    => 'Activity',
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
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Activity\Controller\Activity' => 'Activity\Controller\ActivityController'
        )
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'activity' => __DIR__ . '/../view/'
        )
    ),
    'doctrine' => array(
        'driver' => array(
            'activity_entities' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/Activity/Model/')
            ),
            'orm_default' => array(
                'drivers' => array(
                    'Activity\Model' => 'activity_entities'
                )
            )
        )
    )
);
