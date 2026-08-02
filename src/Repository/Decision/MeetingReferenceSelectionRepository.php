<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingReferenceSelection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MeetingReferenceSelection>
 */
class MeetingReferenceSelectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            MeetingReferenceSelection::class,
        );
    }

    /**
     * The reference documents selected for a meeting with their pinned versions, ordered by document name.
     *
     * @return list<MeetingReferenceSelection>
     */
    public function findForMeeting(Meeting $meeting): array
    {
        $qb = $this->createQueryBuilder('selection');
        $qb->select(
            'selection',
            'document',
            'pinnedVersion',
        )
            ->join(
                'selection.referenceDocument',
                'document',
            )
            ->leftJoin(
                'selection.pinnedVersion',
                'pinnedVersion',
            )
            ->join(
                'selection.meeting',
                'm',
            )
            ->where('m.type = :type')
            ->andWhere('m.number = :number')
            ->orderBy(
                'document.name',
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

        /** @var list<MeetingReferenceSelection> $selections */
        $selections = $qb->getQuery()->getResult();

        return $selections;
    }
}
