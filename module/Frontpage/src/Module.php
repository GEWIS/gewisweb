<?php

namespace Frontpage;

use Doctrine\Laminas\Hydrator\DoctrineObject;
use Frontpage\Form\{
    NewsItem as NewsItemForm,
    Page as PageForm,
    Poll as PollForm,
    PollApproval as PollApprovalForm,
    PollComment as PollCommentForm,
};
use Frontpage\Mapper\{
    NewsItem as NewsItemMapper,
    Page as PageMapper,
    Poll as PollMapper,
};
use Frontpage\Service\{
    AclService,
    Frontpage as FrontpageService,
    News as NewsService,
    Page as PageService,
    Poll as PollService,
};
use Psr\Container\ContainerInterface;
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
    public function getServiceConfig(): array
    {
        return [
            'factories' => [
                'frontpage_service_frontpage' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $aclService = $container->get('decision_service_acl');
                    $pollService = $container->get('frontpage_service_poll');
                    $newsService = $container->get('frontpage_service_news');
                    $memberService = $container->get('decision_service_member');
                    $companyService = $container->get('company_service_company');
                    $photoService = $container->get('photo_service_photo');
                    $tagMapper = $container->get('photo_mapper_tag');
                    $activityMapper = $container->get('activity_mapper_activity');
                    $frontpageConfig = $container->get('config')['frontpage'];
                    $photoConfig = $container->get('config')['photo'];

                    return new FrontpageService(
                        $translator,
                        $aclService,
                        $pollService,
                        $newsService,
                        $memberService,
                        $companyService,
                        $photoService,
                        $tagMapper,
                        $activityMapper,
                        $frontpageConfig,
                        $photoConfig,
                    );
                },
                'frontpage_service_page' => function (ContainerInterface $container) {
                    $aclService = $container->get('frontpage_service_acl');
                    $translator = $container->get('translator');
                    $storageService = $container->get('application_service_storage');
                    $pageMapper = $container->get('frontpage_mapper_page');
                    $pageForm = $container->get('frontpage_form_page');
                    $storageConfig = $container->get('config')['storage'];

                    return new PageService(
                        $aclService,
                        $translator,
                        $storageService,
                        $pageMapper,
                        $pageForm,
                        $storageConfig,
                    );
                },
                'frontpage_service_poll' => function (ContainerInterface $container) {
                    $aclService = $container->get('frontpage_service_acl');
                    $translator = $container->get('translator');
                    $emailService = $container->get('application_service_email');
                    $pollMapper = $container->get('frontpage_mapper_poll');
                    $pollForm = $container->get('frontpage_form_poll');
                    $pollApprovalForm = $container->get('frontpage_form_poll_approval');

                    return new PollService(
                        $aclService,
                        $translator,
                        $emailService,
                        $pollMapper,
                        $pollForm,
                        $pollApprovalForm,
                    );
                },
                'frontpage_service_news' => function (ContainerInterface $container) {
                    $aclService = $container->get('frontpage_service_acl');
                    $translator = $container->get('translator');
                    $newsItemMapper = $container->get('frontpage_mapper_news_item');
                    $newsItemForm = $container->get('frontpage_form_news_item');

                    return new NewsService(
                        $aclService,
                        $translator,
                        $newsItemMapper,
                        $newsItemForm,
                    );
                },
                'frontpage_form_page' => function (ContainerInterface $container) {
                    $form = new PageForm(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_form_poll' => function (ContainerInterface $container) {
                    $form = new PollForm(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_form_poll_comment' => function (ContainerInterface $container) {
                    $form = new PollCommentForm(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_form_poll_approval' => function (ContainerInterface $container) {
                    $form = new PollApprovalForm(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('frontpage_hydrator'));

                    return $form;
                },
                'frontpage_form_news_item' => function (ContainerInterface $container) {
                    $form = new NewsItemForm(
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
                    return new PageMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'frontpage_mapper_poll' => function (ContainerInterface $container) {
                    return new PollMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'frontpage_mapper_news_item' => function (ContainerInterface $container) {
                    return new NewsItemMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'frontpage_service_acl' => function (ContainerInterface $container, $requestedName, array $options = null) {
                    $aclService = (new AclServiceFactory())->__invoke($container, $requestedName, $options);
                    if ($aclService instanceof AclService) {
                        $pages = $container->get('frontpage_mapper_page')->findAll();
                        $aclService->setPages($pages);
                        return $aclService;
                    }
                    throw new RuntimeException(
                        sprintf(
                            'Expected service of type %s, got service of type %s',
                            AclService::class,
                            get_class($aclService)
                        )
                    );
                },
            ],
        ];
    }
}
