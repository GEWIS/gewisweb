<?php

declare(strict_types=1);

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
use DateInterval;
use DateTime;
use Decision\Model\AssociationYear;
use Photo\Model\{
    Photo as PhotoModel,
    WeeklyPhoto as WeeklyPhotoModel,
};

/**
 * Mappers for WeeklyPhoto.
 *
 * @template-extends BaseMapper<WeeklyPhotoModel>
 */
class WeeklyPhoto extends BaseMapper
{
    /**
     * Check whether the given photo has been a photo of the week.
     *
     * @param PhotoModel $photo
     *
     * @return bool
     */
    public function hasBeenPhotoOfTheWeek(PhotoModel $photo): bool
    {
        return !is_null($this->getRepository()->findOneBy(['photo' => $photo]));
    }

    /**
     * @return WeeklyPhotoModel|null
     */
    public function getCurrentPhotoOfTheWeek(): ?WeeklyPhotoModel
    {
        $qb = $this->getRepository()->createQueryBuilder('w');
        $qb->setMaxResults(1)
            ->orderBy('w.week', 'DESC');

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * @return WeeklyPhotoModel|array<array-key, WeeklyPhotoModel>|null
     */
    public function getPhotosOfTheWeekInYear(
        int $year,
        bool $onlyLast = false,
    ): WeeklyPhotoModel|array|null {
        $startDate = DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $year . '-' . AssociationYear::ASSOCIATION_YEAR_START_MONTH . '-'
            . AssociationYear::ASSOCIATION_YEAR_START_DAY . ' 00:00:00'
        );
        $endDate = clone $startDate;
        $endDate->add(new DateInterval('P1Y'));

        $qb = $this->getRepository()->createQueryBuilder('w');
        $qb->where('w.week >= :start')
            ->andWhere('w.week < :end')
            ->setParameter(':start', $startDate)
            ->setParameter(':end', $endDate);

        if ($onlyLast) {
            $qb->orderBy('w.week', 'DESC')
                ->setMaxResults(1);

            return $qb->getQuery()->getOneOrNullResult();
        }

        return $qb->getQuery()->getResult();
    }

    public function getOldestPhotoOfTheWeek(): ?WeeklyPhotoModel
    {
        $qb = $this->getRepository()->createQueryBuilder('w');
        $qb->orderBy('w.week', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getNewestPhotoOfTheWeek(): ?WeeklyPhotoModel
    {
        $qb = $this->getRepository()->createQueryBuilder('w');
        $qb->orderBy('w.week', 'ASC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    protected function getRepositoryName(): string
    {
        return WeeklyPhotoModel::class;
    }
}
