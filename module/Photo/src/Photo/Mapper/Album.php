<?php

namespace Photo\Mapper;

use Photo\Model\Album as AlbumModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for Album.
 *
 */
class Album
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
     * Deletes an album from the database
     *
     * @param integer $id the id of the album
     */
    public function deleteAlbum($id)
    {
        $album = $this->getAlbumById($id);
        if (!is_null($album)) {
            $this->em->remove($album);
        }
    }

    /**
     * retrieves an album by id from the database
     *
     * @param integer $id the id of the album
     *
     * @return Photo\Model\Album
     */
    public function getAlbumById($id)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Album', 'a')
            ->where('a.id = ?1')
            ->setParameter(1, $id);
        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * returns all the subalbums of a given album
     *
     * @param type $parent the parent album to retrieve the subalbum from
     * @param integer $start the result to start at
     * @param integer $maxResults max amount of results to return, null for infinite
     * @return type
     */
    public function getSubAlbums($parent, $start = 0, $maxResults = null)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Album', 'a')
            ->where('a.parent = ?1')
            ->setParameter(1, $parent)
            ->setFirstResult($start);
        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
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
            ->where('a.album = ?1')
            ->setParameter(1, $album)
            ->setFirstResult($start);
        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieves some random photos from the specified album. If the amount of
     * available photos is smaller than the requested count, less photos
     * will be returned.
     *
     * @param int $album
     * @param int $maxResults
     * @return array of Photo\Model\Photo
     */
    public function getRandomAlbumPhotos($album, $maxResults)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Photo', 'a')
            ->where('a.album = ?1')
            ->setParameter(1, $album)
            ->addSelect('RAND() as HIDDEN rand')
            ->orderBy('rand');
        $qb->setMaxResults($maxResults);

        return $qb->getQuery()->getResult();
    }

    /**
     * return all the sub-albums without a parent
     *
     * @return array of AlbumModels
     */
    public function getRootAlbums()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Album', 'a')
            ->where('a.parent IS NULL');

        return $qb->getQuery()->getResult();
    }

    /**
     * Persist album
     *
     * @param AlbumModel $album
     */
    public function persist(AlbumModel $album)
    {
        $this->em->persist($album);
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
        return $this->em->getRepository('Photo\Mapper\Album');
    }

}
