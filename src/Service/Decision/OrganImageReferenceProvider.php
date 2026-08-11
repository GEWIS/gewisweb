<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Decision\OrganInformationRevision;
use App\Service\Application\FileReferenceProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Override;

/**
 * Keeps a body's image alive while any revision of any page still points at its content-addressed path. All four
 * columns count: an uploaded original is what a crop is moved against later, and a revision that was cloned shares both
 * the original and the cut with the one it came from, so any of them referencing the path vetoes its deletion.
 */
final readonly class OrganImageReferenceProvider implements FileReferenceProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Override]
    public function references(string $path): bool
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(revision)')
            ->from(
                OrganInformationRevision::class,
                'revision',
            )
            ->where('revision.bannerPath = :path')
            ->orWhere('revision.logoPath = :path')
            ->orWhere('revision.bannerSource = :path')
            ->orWhere('revision.logoSource = :path')
            ->setParameter(
                'path',
                $path,
            )
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
