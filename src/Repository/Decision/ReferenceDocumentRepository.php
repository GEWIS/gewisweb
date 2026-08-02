<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\MeetingReferenceSelection;
use App\Entity\Decision\ReferenceDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

use function sprintf;

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

    /**
     * All library documents with the number of meetings using each, ordered by name.
     *
     * @return list<array{0: ReferenceDocument, 1: int}>
     */
    public function findAllWithUsageCounts(): array
    {
        $qb = $this->createQueryBuilder('document');
        $qb->addSelect(sprintf(
            '(SELECT COUNT(selection.id) FROM %s selection'
            . ' WHERE selection.referenceDocument = document) AS usageCount',
            MeetingReferenceSelection::class,
        ))
            ->addSelect('version')
            ->leftJoin(
                'document.versions',
                'version',
            )
            ->orderBy(
                'document.name',
                'ASC',
            );

        /** @var list<array{0: ReferenceDocument, usageCount: int<0, max>}> $rows */
        $rows = $qb->getQuery()->getResult();

        $pairs = [];
        foreach ($rows as $row) {
            $pairs[] = [
                $row[0],
                $row['usageCount'],
            ];
        }

        return $pairs;
    }

    /**
     * How many meetings use a library document.
     */
    public function countUsage(ReferenceDocument $document): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(selection.id)')
            ->from(
                MeetingReferenceSelection::class,
                'selection',
            )
            ->where('selection.referenceDocument = :document');

        $qb->setParameter(
            ':document',
            $document->getId(),
            Types::INTEGER,
        );

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
