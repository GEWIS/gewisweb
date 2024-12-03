<?php

declare(strict_types=1);

namespace Frontpage;

use Application\Mapper\Factory\BaseMapperFactory;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Frontpage\Form\Factory\NewsItemFactory as NewsItemFormFactory;
use Frontpage\Form\Factory\PageFactory as PageFormFactory;
use Frontpage\Form\Factory\PollApprovalFactory as PollApprovalFormFactory;
use Frontpage\Form\Factory\PollCommentFactory as PollCommentFormFactory;
use Frontpage\Form\Factory\PollFactory as PollFormFactory;
use Frontpage\Form\NewsItem as NewsItemForm;
use Frontpage\Form\Page as PageForm;
use Frontpage\Form\Poll as PollForm;
use Frontpage\Form\PollApproval as PollApprovalForm;
use Frontpage\Form\PollComment as PollCommentForm;
use Frontpage\Mapper\NewsItem as NewsItemMapper;
use Frontpage\Mapper\Page as PageMapper;
use Frontpage\Mapper\Poll as PollMapper;
use Frontpage\Mapper\PollComment as PollCommentMapper;
use Frontpage\Mapper\PollOption as PollOptionMapper;
use Frontpage\Service\AclService;
use Frontpage\Service\Factory\FrontpageFactory as FrontpageServiceFactory;
use Frontpage\Service\Factory\NewsFactory as NewsServiceFactory;
use Frontpage\Service\Factory\PageFactory as PageServiceFactory;
use Frontpage\Service\Factory\PollFactory as PollServiceFactory;
use Frontpage\Service\Frontpage as FrontpageService;
use Frontpage\Service\News as NewsService;
use Frontpage\Service\Page as PageService;
use Frontpage\Service\Poll as PollService;
use Psr\Container\ContainerInterface;
use RuntimeException;
use User\Authorization\AclServiceFactory;

use function sprintf;

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
                // Services
                AclService::class => static function (
                    ContainerInterface $container,
                    $requestedName,
                    ?array $options = null,
                ) {
                    $aclService = (new AclServiceFactory())->__invoke($container, $requestedName, $options);

                    if ($aclService instanceof AclService) {
                        $pages = $container->get(PageMapper::class)->findAll();
                        $aclService->setPages($pages);

                        return $aclService;
                    }

                    throw new RuntimeException(
                        sprintf(
                            'Expected service of type %s, got service of type %s',
                            AclService::class,
                            $aclService::class,
                        ),
                    );
                },
                FrontpageService::class => FrontpageServiceFactory::class,
                NewsService::class => NewsServiceFactory::class,
                PageService::class => PageServiceFactory::class,
                PollService::class => PollServiceFactory::class,
                // Mappers
                NewsItemMapper::class => BaseMapperFactory::class,
                PageMapper::class => BaseMapperFactory::class,
                PollCommentMapper::class => BaseMapperFactory::class,
                PollMapper::class => BaseMapperFactory::class,
                PollOptionMapper::class => BaseMapperFactory::class,
                // Forms
                NewsItemForm::class => NewsItemFormFactory::class,
                PageForm::class => PageFormFactory::class,
                PollApprovalForm::class => PollApprovalFormFactory::class,
                PollCommentForm::class => PollCommentFormFactory::class,
                PollForm::class => PollFormFactory::class,
                'frontpage_hydrator' => static function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                // Commands
                // N/A
            ],
        ];
    }
}
