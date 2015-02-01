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
     * @param Photo\Model\Photo $photo 
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
     * @param Photo\Model\Photo $photo 
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
     * returns all the photos in an album.
     * 
     * @param Photo\Model\Album $album The album to retrieve the photos from
     * @param integer $start the result to start at
     * @param integer $maxResults max amount of results to return, null for infinite
     * @return array of photo's
     */
    public function getAlbumPhotos($album, $start = 0, $maxResults = null)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
                ->from('Photo\Model\Photo', 'a')
                ->where('a.album = ?1');
        $qb->setParameter(1, $album);
        $qb->setFirstResult($start);
        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Checks if the specified photo exists in the database already and returns
     * it if it does.
     * 
     * @param string $path The storage path of the photo
     * @param Photo\Model\Album $album the album the photo is in
     * @return Photo\Model\Photo if the photo exists, null otherwise.
     */
    public function getPhotoByData($path, $album)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
                ->from('Photo\Model\Photo', 'a')
                ->where('a.path = ?1 AND a.album = ?2');
        $qb->setParameter(1, $path);
        $qb->setParameter(2, $album);
        $res = $qb->getQuery()->getResult();
        return empty($res) ? null : $res[0];        
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
