<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Frontpage\Controller\Frontpage' => 'Frontpage\Controller\FrontpageController',
            'Frontpage\Controller\Page' => 'Frontpage\Controller\PageController',
            'Frontpage\Controller\PageAdmin' => 'Frontpage\Controller\PageAdminController',
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
                                'category' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'sub_category' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'name' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                                '__NAMESPACE__' => 'Frontpage\Controller',
                                'controller' => 'Page',
                                'action' => 'page',
                            ),
                        ),
                        'priority' => -1
                    ),
                ),
            ),
            'admin_page' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/admin/page',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Frontpage\Controller',
                        'controller' => 'PageAdmin',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'create' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/create',
                            'defaults' => array(
                                'action' => 'create',
                            ),
                        ),
                    ),
                    'edit' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[/:page_id]/edit',
                            'constraints' => array(
                                'page_id' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'edit',
                            ),
                        ),
                    ),
                    'delete' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[/:page_id]/delete',
                            'constraints' => array(
                                'page_id' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'delete',
                            ),
                        ),
                    ),
                ),
                'priority' => 100
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
