<?php

declare(strict_types=1);

namespace App\Repository\Photo;

use App\Entity\Decision\AssociationYear;
use App\Entity\Photo\Photo;
use App\Entity\Photo\WeeklyPhoto;
use DateInterval;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
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

    public function getCurrentPhotoOfTheWeek(): ?WeeklyPhoto
    {
        $qb = $this->createQueryBuilder('w');
        $qb->setMaxResults(1)
            ->orderBy(
                'w.week',
                'DESC',
            );

        $res = $qb->getQuery()->getResult();

        return [] === $res
            ? null
            : $res[0];
    }

    /**
     * @return WeeklyPhoto|WeeklyPhoto[]|null
     */
    public function getPhotosOfTheWeekInYear(
        int $year,
        bool $onlyLast = false,
    ): WeeklyPhoto|array|null {
        $startDate = DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $year . '-' . AssociationYear::ASSOCIATION_YEAR_START_MONTH . '-'
            . AssociationYear::ASSOCIATION_YEAR_START_DAY . ' 00:00:00',
        );
        $endDate = clone $startDate;
        $endDate->add(new DateInterval('P1Y'));

        $qb = $this->createQueryBuilder('w');
        $qb->where('w.week >= :start')
            ->andWhere('w.week < :end')
            ->setParameter(
                ':start',
                $startDate,
                Types::DATETIME_MUTABLE,
            )
            ->setParameter(
                ':end',
                $endDate,
                Types::DATETIME_MUTABLE,
            );

        if ($onlyLast) {
            $qb->orderBy(
                'w.week',
                'DESC',
            )
                ->setMaxResults(1);

            return $qb->getQuery()->getOneOrNullResult();
        }

        return $qb->getQuery()->getResult();
    }

    public function getOldestPhotoOfTheWeek(): ?WeeklyPhoto
    {
        $qb = $this->createQueryBuilder('w');
        $qb->orderBy(
            'w.week',
            'DESC',
        )
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getNewestPhotoOfTheWeek(): ?WeeklyPhoto
    {
        $qb = $this->createQueryBuilder('w');
        $qb->orderBy(
            'w.week',
            'ASC',
        )
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
