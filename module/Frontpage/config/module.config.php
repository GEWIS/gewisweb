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
