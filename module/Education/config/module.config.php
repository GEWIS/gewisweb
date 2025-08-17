<?php

declare(strict_types=1);

namespace Education;

use Application\Model\Enums\AuthTypes;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Education\Controller\AdminController;
use Education\Controller\EducationController;
use Education\Controller\Factory\AdminControllerFactory;
use Education\Controller\Factory\EducationControllerFactory;
use Education\Service\Course as CourseService;
use Education\View\Helper\ExamUrl;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Psr\Container\ContainerInterface;

return [
    'router' => [
        'routes' => [
            'education' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/education',
                    'defaults' => [
                        'controller' => EducationController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'course' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/course[/:code]',
                            'constraints' => [
                                'code' => '[a-zA-Z0-9]{5,6}',
                            ],
                            'defaults' => [
                                'action' => 'course',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'download' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/download/:id',
                                    'constraints' => [
                                        'id' => '[0-9]*',
                                    ],
                                    'defaults' => [
                                        'action' => 'download',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'default' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
            'admin_education' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/education',
                    'defaults' => [
                        'auth_type' => AuthTypes::Member,
                        'controller' => AdminController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'course' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/course',
                            'defaults' => [
                                'action' => 'course',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'add' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/add',
                                    'defaults' => [
                                        'action' => 'addCourse',
                                    ],
                                ],
                            ],
                            'documents' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/:course/documents',
                                    'constraints' => [
                                        'course' => '[A-Z0-9]{5,9}',
                                    ],
                                    'defaults' => [
                                        'action' => 'courseDocuments',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'delete' => [
                                        'type' => Segment::class,
                                        'options' => [
                                            'route' => '/:document/delete',
                                            'constraints' => [
                                                'document' => '\d+',
                                            ],
                                            'defaults' => [
                                                'action' => 'deleteCourseDocument',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/:course/edit',
                                    'constraints' => [
                                        'course' => '[A-Z0-9]{5,9}',
                                    ],
                                    'defaults' => [
                                        'action' => 'editCourse',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/:course/delete',
                                    'constraints' => [
                                        'course' => '[A-Z0-9]{5,9}',
                                    ],
                                    'defaults' => [
                                        'action' => 'deleteCourse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'bulk_upload_exam' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/bulk/exam',
                            'defaults' => [
                                'action' => 'bulkExam',
                            ],
                        ],
                    ],
                    'bulk_upload_summary' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/bulk/summary',
                            'defaults' => [
                                'action' => 'bulkSummary',
                            ],
                        ],
                    ],
                    'bulk_edit_exam' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/edit/exam',
                            'defaults' => [
                                'action' => 'editExam',
                            ],
                        ],
                    ],
                    'bulk_edit_summary' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/edit/summary',
                            'defaults' => [
                                'action' => 'editSummary',
                            ],
                        ],
                    ],
                    'delete_temp' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:type/:filename/delete',
                            'constraints' => [
                                'type' => 'exam|summary',
                            ],
                            'defaults' => [
                                'action' => 'deleteTemp',
                            ],
                        ],
                    ],
                ],
                'priority' => 100,
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            AdminController::class => AdminControllerFactory::class,
            EducationController::class => EducationControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'education' => __DIR__ . '/../view/',
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'examUrl' => static function (ContainerInterface $container) {
                $config = $container->get('config');
                $helper = new ExamUrl();
                $helper->setDir($config['education']['public_dir']);
                $helper->setCourseService($container->get(CourseService::class));

                return $helper;
            },
        ],
    ],
    'doctrine' => [
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => AttributeDriver::class,
                'paths' => [
                    __DIR__ . '/../src/Model/',
                ],
            ],
            'orm_default' => [
                'drivers' => [
                    __NAMESPACE__ . '\Model' => __NAMESPACE__ . '_driver',
                ],
            ],
        ],
    ],
];
