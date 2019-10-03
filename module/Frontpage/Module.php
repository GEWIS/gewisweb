<?php

namespace Frontpage;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Frontpage\Form\NewsItem;
use Frontpage\Form\Page;
use Frontpage\Form\Poll;
use Frontpage\Form\PollApproval;
use Frontpage\Form\PollComment;

class Module
{
    /**
     * Get the autoloader configuration.
     *
     * @return array Autoloader config
     */
    public function getAutoloaderConfig()
    {
        if (APP_ENV === 'production') {
            return [
                'Zend\Loader\ClassMapAutoloader' => [
                    __DIR__ . '/autoload_classmap.php',
                ]
            ];
        }

        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ]
            ]
        ];
    }

    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig()
    {
        return [
            'invokables' => [
                'frontpage_service_frontpage' => 'Frontpage\Service\Frontpage',
                'frontpage_service_page' => 'Frontpage\Service\Page',
                'frontpage_service_poll' => 'Frontpage\Service\Poll',
                'frontpage_service_news' => 'Frontpage\Service\News'
            ],
            'factories' => [
                'frontpage_form_page' => function ($sm) {
                    $form = new Page(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_form_poll' => function ($sm) {
                    $form = new Poll(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_form_poll_comment' => function ($sm) {
                    $form = new PollComment(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('frontpage_hydrator'));
                    return $form;
                },
                'frontpage_form_poll_approval' => function ($sm) {
                    $form = new PollApproval(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_form_news_item' => function ($sm) {
                    $form = new NewsItem(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_hydrator' => function ($sm) {
                    return new DoctrineObject(
                        $sm->get('frontpage_doctrine_em')
                    );
                },
                'frontpage_mapper_page' => function ($sm) {
                    return new Mapper\Page(
                        $sm->get('frontpage_doctrine_em')
                    );
                },
                'frontpage_mapper_poll' => function ($sm) {
                    return new Mapper\Poll(
                        $sm->get('frontpage_doctrine_em')
                    );
                },
                'frontpage_mapper_news_item' => function ($sm) {
                    return new Mapper\NewsItem(
                        $sm->get('frontpage_doctrine_em')
                    );
                },
                'frontpage_acl' => function ($sm) {
                    $acl = $sm->get('acl');

                    $acl->addResource('page');
                    $acl->addResource('poll');
                    $acl->addResource('poll_comment');
                    $acl->addResource('news_item');

                    $acl->allow('user', 'poll', ['vote', 'request']);
                    $acl->allow('user', 'poll_comment', ['view', 'create', 'list']);

                    return $acl;
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                // reused code from the eduction module
                'frontpage_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            ]
        ];
    }
}
