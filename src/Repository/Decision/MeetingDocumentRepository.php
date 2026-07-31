<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\Meeting;
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

    /**
     * All documents of a meeting with their versions, in display order.
     *
     * @return list<MeetingDocument>
     */
    public function findForMeeting(Meeting $meeting): array
    {
        $qb = $this->createQueryBuilder('document');
        $qb->select(
            'document',
            'version',
        )
            ->leftJoin(
                'document.versions',
                'version',
            )
            ->join(
                'document.meeting',
                'm',
            )
            ->where('m.type = :type')
            ->andWhere('m.number = :number')
            ->orderBy(
                'document.displayPosition',
                'ASC',
            )
            ->addOrderBy(
                'document.id',
                'ASC',
            );

        $qb->setParameter(
            ':type',
            $meeting->getType(),
        );
        $qb->setParameter(
            ':number',
            $meeting->getNumber(),
        );

        /** @var list<MeetingDocument> $documents */
        $documents = $qb->getQuery()->getResult();

        return $documents;
    }
}
