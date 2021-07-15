<?php

namespace Frontpage;

use Doctrine\Laminas\Hydrator\DoctrineObject;
use Frontpage\Form\NewsItem;
use Frontpage\Form\Page;
use Frontpage\Form\Poll;
use Frontpage\Form\PollApproval;
use Frontpage\Form\PollComment;
use Frontpage\Service\AclService;
use Frontpage\Service\Frontpage;
use Frontpage\Service\News;
use Interop\Container\ContainerInterface;
use RuntimeException;
use User\Authorization\AclServiceFactory;

class Module
{
    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
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
                'frontpage_service_frontpage' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $pollService = $container->get('frontpage_service_poll');
                    $newsService = $container->get('frontpage_service_news');
                    $memberService = $container->get('decision_service_member');
                    $companyService = $container->get('company_service_company');
                    $photoService = $container->get('photo_service_photo');
                    $tagMapper = $container->get('photo_mapper_tag');
                    $activityMapper = $container->get('activity_mapper_activity');
                    $frontpageConfig = $container->get('config')['frontpage'];

                    return new Frontpage(
                        $translator,
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
                'frontpage_service_page' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $storageService = $container->get('application_service_storage');
                    $pageMapper = $container->get('frontpage_mapper_page');
                    $pageForm = $container->get('frontpage_form_page');
                    $storageConfig = $container->get('config')['storage'];
                    $aclService = $container->get('frontpage_service_acl');

                    return new Service\Page(
                        $translator,
                        $storageService,
                        $pageMapper,
                        $pageForm,
                        $storageConfig,
                        $aclService
                    );
                },
                'frontpage_service_poll' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $emailService = $container->get('application_service_email');
                    $pollMapper = $container->get('frontpage_mapper_poll');
                    $pollForm = $container->get('frontpage_form_poll');
                    $pollCommentForm = $container->get('frontpage_form_poll_comment');
                    $pollApprovalForm = $container->get('frontpage_form_poll_approval');
                    $aclService = $container->get('frontpage_service_acl');

                    return new Service\Poll(
                        $translator,
                        $emailService,
                        $pollMapper,
                        $pollForm,
                        $pollCommentForm,
                        $pollApprovalForm,
                        $aclService
                    );
                },
                'frontpage_service_news' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $newsItemMapper = $container->get('frontpage_mapper_news_item');
                    $newsItemForm = $container->get('frontpage_form_news_item');
                    $aclService = $container->get('frontpage_service_acl');

                    return new News($translator, $newsItemMapper, $newsItemForm, $aclService);
                },
                'frontpage_form_page' => function (ContainerInterface $container) {
                    $form = new Page(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_form_poll' => function (ContainerInterface $container) {
                    $form = new Poll(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_form_poll_comment' => function (ContainerInterface $container) {
                    $form = new PollComment(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_form_poll_approval' => function (ContainerInterface $container) {
                    $form = new PollApproval(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_form_news_item' => function (ContainerInterface $container) {
                    $form = new NewsItem(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_hydrator' => function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'frontpage_mapper_page' => function (ContainerInterface $container) {
                    return new Mapper\Page(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'frontpage_mapper_poll' => function (ContainerInterface $container) {
                    return new Mapper\Poll(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'frontpage_mapper_news_item' => function (ContainerInterface $container) {
                    return new Mapper\NewsItem(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'frontpage_service_acl' => function (ContainerInterface $container, $requestedName, array $options = null) {
                    $aclService = (new AclServiceFactory())->__invoke($container, $requestedName, $options);
                    if (get_class($aclService) !== AclService::class) {
                        throw new RuntimeException(
                            sprintf(
                                'Expected service of type %s, got service of type %s',
                                AclService::class,
                                get_class($aclService)
                            )
                        );
                    }
                    $pages = $container->get('frontpage_mapper_page')->getAllPages();
                    $aclService->setPages($pages);
                    return $aclService;
                },
            ],
        ];
    }
}
