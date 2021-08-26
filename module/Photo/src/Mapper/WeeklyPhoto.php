<?php

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Photo\Model\WeeklyPhoto as WeeklyPhotoModel;

/**
 * Mappers for WeeklyPhoto.
 */
class WeeklyPhoto extends BaseMapper
{
    /**
     * Check whether the given photo has been a photo of the week.
     *
     * @param \Photo\Model\Photo $photo
     *
     * @return bool
     */
    public function hasBeenPhotoOfTheWeek($photo)
    {
        return !is_null($this->getRepository()->findOneBy(['photo' => $photo]));
    }

    public function getCurrentPhotoOfTheWeek()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('w')
            ->from($this->getRepositoryName(), 'w')
            ->setMaxResults(1)
            ->orderBy('w.week', 'DESC');

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Retrieves all WeeklyPhotos.
     *
     * @return array
     */
    public function getPhotosOfTheWeek()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('w')
            ->from($this->getRepositoryName(), 'w')
            ->orderBy('w.week', 'DESC');

        return $qb->getQuery()->getResult();
    }

    protected function getRepositoryName(): string
    {
        return WeeklyPhotoModel::class;
    }
}
