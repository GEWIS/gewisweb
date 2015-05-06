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
     * retrieves a photo by id from the database
     * 
     * @param integer $id the id of the photo
     * 
     * @return Photo\Model\Photo
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
     * Deletes a photo from the database
     * 
     * @param integer $id the id of the photo 
     */
    public function deletePhoto($id)
    {
        $photo = $this->getPhotoById($id);
        if (!is_null($photo)){
            $this->em->remove($photo);
        }
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
