<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Decision\OrganInformation;
use App\Service\Application\FileReferenceProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Override;

/**
 * Keeps an organ cover or thumbnail alive while any organ still points at its content-addressed path. Both the cover
 * and the thumbnail live in the same namespace and could share bytes, so either column referencing the path vetoes its
 * deletion.
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
            ->select('COUNT(organ)')
            ->from(
                OrganInformation::class,
                'organ',
            )
            ->where('organ.coverPath = :path')
            ->orWhere('organ.thumbnailPath = :path')
            ->setParameter(
                'path',
                $path,
            )
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
