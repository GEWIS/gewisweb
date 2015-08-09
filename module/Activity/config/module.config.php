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
                    'view' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/view/[:id]',
                            'constraints' => array(
                                'action'     => '[0-9]*',
                            ),
                            'defaults' => array(
                                'action' => 'view'
                            )
                        ),
                    ),
                    'signup' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/signup/[:id]',
                            'constraints' => array(
                                'action'     => '[0-9]*',
                            ),
                            'defaults' => array(
                                'action' => 'signup'
                            )
                        ),
                    ),
					'signoff' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/signoff/[:id]',
                            'constraints' => array(
                                'action'     => '[0-9]*',
                            ),
                            'defaults' => array(
                                'action' => 'signoff'
                            )
                        ),
                    ),
                    'create' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/create',
                            'defaults' => array (
                                'action' => 'create'
                            )
                        )
                    ),
                    'admin_queue' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/admin/queue',
                            'defaults' => array(
                                'controller' => 'admin',
                                'action' => 'queue'
                            )
                        )
                    ),
                    'admin_view' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/admin/view/[:id]',
                            'defaults' => array(
                                'controller' => 'admin',
                                'action' => 'view'
                            )
                        )
                    ),
                    'admin_approve' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/admin/approve/[:id]',
                            'defaults' => array(
                                'controller' => 'admin',
                                'action' => 'approve'
                            )
                        )
                    ),
                    'admin_disapprove' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/admin/disapprove/[:id]',
                            'defaults' => array(
                                'controller' => 'admin',
                                'action' => 'disapprove'
                            )
                        )
                    ),
                    'admin_reset' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/admin/reset/[:id]',
                            'defaults' => array(
                                'controller' => 'admin',
                                'action' => 'reset'
                            )
                        )
                    )
                ),
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Activity\Controller\Activity' => 'Activity\Controller\ActivityController',
            'Activity\Controller\Admin' => 'Activity\Controller\AdminController'
        ),
        'factories' => array(
            'Activity\Controller\Activity' => function ($sm) {
                $controller = new Activity\Controller\ActivityController;
                $activity = $sm->getServiceLocator()->get('activity_service');
                $controller->setActivity($activity);
                return $controller;
            },
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
