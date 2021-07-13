<?php

namespace Photo\Mapper;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Mappers for WeeklyPhoto.
 */
class WeeklyPhoto
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

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
            ->from('Photo\Model\WeeklyPhoto', 'w')
            ->setMaxResults(1)
            ->orderBy('w.week', 'DESC');

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Retrieves all WeeklyPhotos.
     *
     * @return Collection
     */
    public function getPhotosOfTheWeek()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('w')
            ->from('Photo\Model\WeeklyPhoto', 'w')
            ->orderBy('w.week', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Persist weeklyPhoto.
     */
    public function persist(\Photo\Model\WeeklyPhoto $weeklyPhoto)
    {
        $this->em->persist($weeklyPhoto);
    }

    /**
     * Flush.
     */
    public function flush()
    {
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Photo\Model\WeeklyPhoto');
    }
}
