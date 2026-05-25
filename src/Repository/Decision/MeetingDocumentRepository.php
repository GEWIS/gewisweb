<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\MeetingDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MeetingDocument>
 */
class MeetingDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            MeetingDocument::class,
        );
    }
}
