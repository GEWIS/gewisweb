<?php

namespace Photo\Mapper;

use Photo\Model\Photo as PhotoModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for Photo.
 * 
 */
class Photo
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
     * Returns the next photo in the album to display
     * 
     * @param \Photo\Model\Photo $photo 
     */
    public function getNextPhoto($photo)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
                ->from('Photo\Model\Photo', 'a')
                ->where('a.id > ?1 AND a.album = ?2');
        $qb->setParameter(1, $photo->getId());
        $qb->setParameter(2, $photo->getAlbum());
        $qb->addOrderBy('a.id', 'ASC');
        $qb->setMaxResults(1);
        $res = $qb->getQuery()->getResult();
        return empty($res) ? null : $res[0];
    }

    /**
     * Returns the previous photo in the album to display
     * 
     * @param \Photo\Model\Photo $photo 
     */
    public function getPreviousPhoto($photo)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
                ->from('Photo\Model\Photo', 'a')
                ->where('a.id < ?1 AND a.album = ?2');
        $qb->setParameter(1, $photo->getId());
        $qb->setParameter(2, $photo->getAlbum());
        $qb->addOrderBy('a.id', 'DESC');
        $qb->setMaxResults(1);
        $res = $qb->getQuery()->getResult();
        return empty($res) ? null : $res[0];
    }

    /**
     * Returns a random photo from the specified album
     * 
     * @param Photo\Model\Album $album
     * @return Photo\Model\Photo
     */
    public function getRandomPhoto($album)
    {
        $qb = $this->em->createQueryBuilder();

        /**
         * This is not exactly the right way to do this. There is no way to just
         * get a random row, unless we use a raw sql query.
         * The doctrine team refuses to implement any 
         * random functionality. (http://www.doctrine-project.org/jira/browse/DDC-950)
         * 
         */
        $qb->select('a')
                ->from('Photo\Model\Photo', 'a')
                ->where('a.album = ?1');
        $qb->setParameter(1, $album);
        $qb->setMaxResults(20);
        $res = $qb->getQuery()->getResult();
        return empty($res) ? null : $res[array_rand($res)];
    }
    /**
     * returns all the photos in an album.
     * 
     * @param \Photo\Model\Album $album The album to retrieve the photos from
     * @param integer $start the result to start at
     * @param integer $max_results max amount of results to return, null for infinite
     * @return array of photo's
     */
    public function getAlbumPhotos($album, $start = 0, $max_results = null)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
                ->from('Photo\Model\Photo', 'a')
                ->where('a.album = ?1');
        $qb->setParameter(1, $album);
        $qb->setFirstResult($start);
        if (!is_null($max_results)) {
            $qb->setMaxResults($max_results);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * retrieves an album by id from the database
     * 
     * @param integer $id the id of the album
     * 
     * @return Photo\Model\Album
     */
    public function getPhotoById($id)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
                ->from('Photo\Model\Photo', 'a')
                ->where('a.id = ?1');
        $qb->setParameter(1, $id);
        $res = $qb->getQuery()->getResult();
        return empty($res) ? null : $res[0];
    }

    /**
     * Persist photo
     *
     * @param PhotoModel $photo
     */
    public function persist(PhotoModel $photo)
    {
        $this->em->persist($photo);
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
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Photo\Mapper\Photo');
    }

}
