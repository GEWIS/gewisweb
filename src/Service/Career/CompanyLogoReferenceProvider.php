<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Career\CompanyRevision;
use App\Service\Application\FileReferenceProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Override;

/**
 * Keeps a company logo alive while any revision, in any approval chain, still points at its content-addressed path.
 * Cloning a revision carries the logo path forward by value, so several revisions share one physical file; the file may
 * only be reclaimed once the last referencing revision is gone.
 */
final readonly class CompanyLogoReferenceProvider implements FileReferenceProviderInterface
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
                CompanyRevision::class,
                'revision',
            )
            ->where('revision.logo = :path')
            ->setParameter(
                'path',
                $path,
            )
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
