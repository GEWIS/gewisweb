<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\ReferenceDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReferenceDocument>
 */
class ReferenceDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ReferenceDocument::class,
        );
    }
}
