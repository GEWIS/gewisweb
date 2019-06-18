<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return [
    'router' => [
        'routes' => [
            'lang' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/lang/:lang/',
                    'defaults' => [
                        '__NAMESPACE__' => 'Application\Controller',
                        'controller'    => 'Index',
                        'action'        => 'lang',
                        'lang'          => 'nl'
                    ]
                ],
                'priority' => 100
            ],
        ],
    ],
    'service_manager' => [
        'abstract_factories' => [
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ],
        'aliases' => [
            'translator' => 'MvcTranslator',
        ],
    ],
    'translator' => [
        'locale' => 'nl',
        'translation_file_patterns' => [
            [
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ],
            // Zend\Validate translation
            [
                'type' => 'phparray',
                'base_dir' => 'vendor/zendframework/zendframework/resources/languages/',
                'pattern' => '%s/Zend_Validate.php',
                'text_domain' => 'validate'
            ]
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Application\Controller\Index' => 'Application\Controller\IndexController'
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => (APP_ENV === 'production' ? 'error/404' : 'error/debug/404'),
        'exception_template'       => (APP_ENV === 'production' ? 'error/500' : 'error/debug/500'),
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/403'               => __DIR__ . '/../view/error/403.phtml',
            'error/500'             => __DIR__ . '/../view/error/500.phtml',
            'error/debug/404'               => __DIR__ . '/../view/error/debug/404.phtml',
            'error/debug/403'               => __DIR__ . '/../view/error/debug/403.phtml',
            'error/debug/500'             => __DIR__ . '/../view/error/debug/500.phtml',
            'paginator/default'       => __DIR__ . '/../view/partial/paginator.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'featuredCompanyPackage' => 'Application\View\Helper\FeaturedCompanyPackage',
            'bootstrapElementError'  => 'Application\View\Helper\BootstrapElementError',
        ],
    ],
];
