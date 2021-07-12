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
use Laminas\ServiceManager\ServiceLocatorInterface;

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
                'frontpage_service_frontpage' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('frontpage_acl');
                    $pollService = $sm->get('frontpage_service_poll');
                    $newsService = $sm->get('frontpage_service_news');
                    $memberService = $sm->get('decision_service_member');
                    $companyService = $sm->get('company_service_company');
                    $photoService = $sm->get('photo_service_photo');
                    $tagMapper = $sm->get('photo_mapper_tag');
                    $activityMapper = $sm->get('activity_mapper_activity');
                    $frontpageConfig = $sm->get('config')['frontpage'];
                    return new Frontpage(
                        $translator,
                        $userRole,
                        $acl,
                        $pollService,
                        $newsService,
                        $memberService,
                        $companyService,
                        $photoService,
                        $tagMapper,
                        $activityMapper,
                        $frontpageConfig
                    );
                },
                'frontpage_service_page' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('frontpage_acl');
                    $storageService = $sm->get('application_service_storage');
                    $pageMapper = $sm->get('frontpage_mapper_page');
                    $pageForm = $sm->get('frontpage_form_page');
                    $storageConfig = $sm->get('config')['storage'];
                    return new Service\Page(
                        $translator,
                        $userRole,
                        $acl,
                        $storageService,
                        $pageMapper,
                        $pageForm,
                        $storageConfig
                    );
                },
                'frontpage_service_poll' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('frontpage_acl');
                    $emailService = $sm->get('application_service_email');
                    $pollMapper = $sm->get('frontpage_mapper_poll');
                    $pollForm = $sm->get('frontpage_form_poll');
                    $pollCommentForm = $sm->get('frontpage_form_poll_comment');
                    $pollApprovalForm = $sm->get('frontpage_form_poll_approval');
                    return new Service\Poll(
                        $translator,
                        $userRole,
                        $acl,
                        $emailService,
                        $pollMapper,
                        $pollForm,
                        $pollCommentForm,
                        $pollApprovalForm
                    );
                },
                'frontpage_service_news' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('frontpage_acl');
                    $newsItemMapper = $sm->get('frontpage_mapper_news_item');
                    $newsItemForm = $sm->get('frontpage_form_news_item');
                    return new News($translator, $userRole, $acl, $newsItemMapper, $newsItemForm);
                },
                'frontpage_form_page' => function (ServiceLocatorInterface $sm) {
                    $form = new Page(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_form_poll' => function (ServiceLocatorInterface $sm) {
                    $form = new Poll(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_form_poll_comment' => function (ServiceLocatorInterface $sm) {
                    $form = new PollComment(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('frontpage_hydrator'));
                    return $form;
                },
                'frontpage_form_poll_approval' => function (ServiceLocatorInterface $sm) {
                    $form = new PollApproval(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_form_news_item' => function (ServiceLocatorInterface $sm) {
                    $form = new NewsItem(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_hydrator' => function (ServiceLocatorInterface $sm) {
                    return new DoctrineObject(
                        $sm->get('frontpage_doctrine_em')
                    );
                },
                'frontpage_mapper_page' => function (ServiceLocatorInterface $sm) {
                    return new Mapper\Page(
                        $sm->get('frontpage_doctrine_em')
                    );
                },
                'frontpage_mapper_poll' => function (ServiceLocatorInterface $sm) {
                    return new Mapper\Poll(
                        $sm->get('frontpage_doctrine_em')
                    );
                },
                'frontpage_mapper_news_item' => function (ServiceLocatorInterface $sm) {
                    return new Mapper\NewsItem(
                        $sm->get('frontpage_doctrine_em')
                    );
                },
                'frontpage_acl' => function (ServiceLocatorInterface $sm) {
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
                'frontpage_doctrine_em' => function (ServiceLocatorInterface $sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            ]
        ];
    }
}
