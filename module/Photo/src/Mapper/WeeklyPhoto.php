<?php

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
use Photo\Model\{
    Photo as PhotoModel,
    WeeklyPhoto as WeeklyPhotoModel,
};

/**
 * Mappers for WeeklyPhoto.
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
     * Retrieves all WeeklyPhotos.
     *
     * @return array
     */
    public function getPhotosOfTheWeek(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('w');
        $qb->orderBy('w.week', 'DESC');

        return $qb->getQuery()->getResult();
    }

    protected function getRepositoryName(): string
    {
        return WeeklyPhotoModel::class;
    }
}
