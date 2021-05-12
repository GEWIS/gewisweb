<?php

namespace Photo\Mapper;

use Doctrine\ORM\EntityManager;

/**
 * Mappers for WeeklyPhoto.
 *
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
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    
    /**
     * Check whether the given photo has been a photo of the week.
     * 
     * @param \Photo\Model\Photo $photo
     * @return boolean
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
     * Retrieves all WeeklyPhotos
     *
     * @return array
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
     * Persist weeklyPhoto
     *
     * @param \Photo\Model\WeeklyPhoto $weeklyPhoto
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
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Photo\Model\WeeklyPhoto');
    }

}
