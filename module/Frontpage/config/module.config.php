<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Frontpage\Controller\Frontpage' => 'Frontpage\Controller\FrontpageController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'home' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Frontpage\Controller',
                        'controller'    => 'Frontpage',
                        'action'        => 'home',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'page' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/[:category[/:name]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'Frontpage' => __DIR__ . '/../view',
        ),
    ),
);
