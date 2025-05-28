<?php

declare(strict_types=1);

namespace Company\Service\Factory;

use Company\Mapper\Category as JobCategoryMapper;
use Company\Mapper\Job as JobMapper;
use Company\Mapper\Label as JobLabelMapper;
use Company\Service\AclService;
use Company\Service\CompanyQuery as CompanyQueryService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class CompanyQueryFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): CompanyQueryService {
        return new CompanyQueryService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(JobMapper::class),
            $container->get(JobCategoryMapper::class),
            $container->get(JobLabelMapper::class),
        );
    }
}
