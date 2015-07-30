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
     * @return \Photo\Model\Album|null Photo if there is a next
     * photo, null otherwise
     */
    public function getNextPhoto($photo)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Photo', 'a')
            ->where('a.id > ?1 AND a.album = ?2')
            ->setParameter(1, $photo->getId())
            ->setParameter(2, $photo->getAlbum())
            ->addOrderBy('a.id', 'ASC')
            ->setMaxResults(1);
        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Returns the previous photo in the album to display
     *
     * @param \Photo\Model\Photo $photo
     *
     * @return \Photo\Model\Album|null Photo if there is a previous
     * photo, null otherwise
     */
    public function getPreviousPhoto($photo)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Photo', 'a')
            ->where('a.id < ?1 AND a.album = ?2')
            ->setParameter(1, $photo->getId())
            ->setParameter(2, $photo->getAlbum())
            ->addOrderBy('a.id', 'DESC')
            ->setMaxResults(1);
        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Checks if the specified photo exists in the database already and returns
     * it if it does.
     *
     * @param string $path The storage path of the photo
     * @param \Photo\Model\Album $album the album the photo is in
     * @return \Photo\Model\Photo|null
     */
    public function getPhotoByData($path, $album)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Photo', 'a')
            ->where('a.path = ?1 AND a.album = ?2')
            ->setParameter(1, $path)
            ->setParameter(2, $album);
        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }


    /**
     * Retrieves a photo by id from the database.
     *
     * @param integer $photoId the id of the photo
     *
     * @return \Photo\Model\Photo
     */
    public function getPhotoById($photoId)
    {
        return $this->getRepository()->find($photoId);
    }

    /**
     * Removes a photo
     *
     * @param \Photo\Model\Photo $photo
     */
    public function remove(PhotoModel $photo)
    {
        $this->em->remove($photo);
    }
    /**
     * Persist photo
     *
     * @param \Photo\Model\Photo $photo
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
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Photo\Model\Photo');
    }

}
