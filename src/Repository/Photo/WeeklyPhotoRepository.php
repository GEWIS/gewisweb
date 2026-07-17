<?php

declare(strict_types=1);

namespace App\Repository\Photo;

use App\Entity\Photo\Photo;
use App\Entity\Photo\WeeklyPhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeeklyPhoto>
 */
class WeeklyPhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            WeeklyPhoto::class,
        );
    }

    /**
     * Check whether the given photo has been a photo of the week.
     */
    public function hasBeenPhotoOfTheWeek(Photo $photo): bool
    {
        return null !== $this->findOneBy(['photo' => $photo]);
    }

    /**
     * Every photo of the week, most recent first, with its photo fetch-joined so the weekly archive can render each
     * without a per-row lazy load.
     *
     * @return WeeklyPhoto[]
     */
    public function findAllByWeekDesc(): array
    {
        return $this->createQueryBuilder('w')
            ->leftJoin(
                'w.photo',
                'photo',
            )
            ->addSelect('photo')
            ->orderBy(
                'w.week',
                'DESC',
            )
            ->getQuery()
            ->getResult();
    }

    public function getCurrentPhotoOfTheWeek(): ?WeeklyPhoto
    {
        $qb = $this->createQueryBuilder('w');
        $qb->setMaxResults(1)
            ->orderBy(
                'w.week',
                'DESC',
            )
            // Tiebreak so a regenerated pick for the same week (a later row) wins over the one it replaces.
            ->addOrderBy(
                'w.id',
                'DESC',
            );

        $res = $qb->getQuery()->getResult();

        return [] === $res
            ? null
            : $res[0];
    }
}
