<?php

declare(strict_types=1);

namespace Frontpage\Service\Factory;

use Activity\Mapper\Activity as ActivityMapper;
use Company\Service\Company as CompanyService;
use Decision\Service\AclService as DecisionAclService;
use Decision\Service\Member as MemberService;
use Frontpage\Service\Frontpage as FrontpageService;
use Frontpage\Service\News as NewsService;
use Frontpage\Service\Poll as PollService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Photo\Mapper\Tag as TagMapper;
use Photo\Service\Photo as PhotoService;
use Psr\Container\ContainerInterface;

class FrontpageFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): FrontpageService {
        return new FrontpageService(
            $container->get(MvcTranslator::class),
            $container->get(DecisionAclService::class),
            $container->get(PollService::class),
            $container->get(NewsService::class),
            $container->get(MemberService::class),
            $container->get(CompanyService::class),
            $container->get(PhotoService::class),
            $container->get(TagMapper::class),
            $container->get(ActivityMapper::class),
            $container->get('config')['frontpage'],
            $container->get('config')['photo'],
        );
    }
}
