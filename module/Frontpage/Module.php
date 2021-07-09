<?php

namespace Frontpage;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Frontpage\Form\NewsItem;
use Frontpage\Form\Page;
use Frontpage\Form\Poll;
use Frontpage\Form\PollApproval;
use Frontpage\Form\PollComment;
use Frontpage\Service\Frontpage;
use Frontpage\Service\News;

class Module
{
    /**
     * Get the autoloader configuration.
     */
    public function getAutoloaderConfig()
    {
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
            'factories' => [
                'frontpage_service_frontpage' => function ($sm) {
                    $translator = $sm->get('translator');
                    return new Frontpage($translator);
                },
                'frontpage_service_page' => function ($sm) {
                    $translator = $sm->get('translator');
                    return new Service\Page($translator);
                },
                'frontpage_service_poll' => function ($sm) {
                    $translator = $sm->get('translator');
                    return new Service\Poll($translator);
                },
                'frontpage_service_news' => function ($sm) {
                    $translator = $sm->get('translator');
                    return new News($translator);
                },
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
