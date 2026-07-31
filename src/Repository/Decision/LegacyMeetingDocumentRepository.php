<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\LegacyMeetingDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Only used by the one-shot legacy document migrator; dropped together with the entity.
 *
 * @internal
 *
 * @extends ServiceEntityRepository<LegacyMeetingDocument>
 */
class LegacyMeetingDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            LegacyMeetingDocument::class,
        );
    }

    /**
     * @return list<LegacyMeetingDocument>
     */
    public function findAllOrderedById(): array
    {
        return $this->findBy(
            [],
            ['id' => 'ASC'],
        );
    }
}
