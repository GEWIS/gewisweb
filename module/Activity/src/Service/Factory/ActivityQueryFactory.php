<?php

declare(strict_types=1);

namespace Activity\Service\Factory;

use Activity\Mapper\Activity as ActivityMapper;
use Activity\Mapper\Proposal as ProposalMapper;
use Activity\Service\AclService;
use Activity\Service\ActivityQuery as ActivityQueryService;
use Decision\Service\Organ as OrganService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class ActivityQueryFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ActivityQueryService {
        return new ActivityQueryService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(OrganService::class),
            $container->get(ActivityMapper::class),
            $container->get(ProposalMapper::class),
        );
    }
}
