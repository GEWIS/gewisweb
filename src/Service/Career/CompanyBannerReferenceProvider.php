<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Career\CompanyBannerPackage;
use App\Service\Application\FileReferenceProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Override;

/**
 * Keeps a company banner image alive while any banner package still points at its content-addressed path. Banners share
 * the per-company image namespace with logos, so the same bytes could in principle back more than one package.
 */
final readonly class CompanyBannerReferenceProvider implements FileReferenceProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Override]
    public function references(string $path): bool
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(package)')
            ->from(
                CompanyBannerPackage::class,
                'package',
            )
            ->where('package.image = :path')
            ->setParameter(
                'path',
                $path,
            )
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
