<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Frontpage\Controller\Frontpage' => 'Frontpage\Controller\FrontpageController',
            'Frontpage\Controller\Page' => 'Frontpage\Controller\PageController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Frontpage\Controller',
                        'controller' => 'Frontpage',
                        'action' => 'home',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'page' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[:category[/:sub_category][/:name]]',
                            'constraints' => array(
                                'category' => '[a-zA-Z][a-zA-Z0-9_-]+',
                                'sub_category' => '[a-zA-Z][a-zA-Z0-9_-]+',
                                'name' => '[a-zA-Z][a-zA-Z0-9_-]+',
                            ),
                            'defaults' => array(
                                '__NAMESPACE__' => 'Frontpage\Controller',
                                'controller' => 'Page',
                                'action' => 'page',
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
    'doctrine' => array(
        'driver' => array(
            'frontpage_entities' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/Frontpage/Model/')
            ),
            'orm_default' => array(
                'drivers' => array(
                    'Frontpage\Model' => 'frontpage_entities'
                )
            )
        )
    ),
);
