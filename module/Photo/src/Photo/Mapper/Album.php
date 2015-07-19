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
     * Retrieves an album by id from the database.
     *
     * @param integer $id the id of the album
     *
     * @return \Photo\Model\Album
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
     * Returns all the subalbums of a given album.
     *
     * @param \Photo\Model\Album $parent the parent album to retrieve the subalbum from
     * @param integer $start the result to start at
     * @param integer $maxResults max amount of results to return, null for infinite
     * @return array|null Array with subalbums or null if there are none
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
     * Returns all the photos in an album.
     *
     * @param \Photo\Model\Album $album The album to retrieve the photos from
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
     * @return array of \Photo\Model\Album
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
     * Gets all root albums with a start date between the specified dates
     *
     * @param $start \DateTime start date and time
     * @param $end \DateTime end date and time
     *
     * @return array of \Photo\Model\Album
     */
    public function getAlbumsInDateRange($start, $end) {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Album', 'a')
            ->where('a.parent IS NULL')
            ->andWhere('a.startDateTime BETWEEN ?1 AND ?2')
            ->setParameter(1, $start)
            ->setParameter(2, $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the root album containing the most recent photos
     *
     * @return \Photo\Model\Album
     */
    public function getNewestAlbum() {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Album', 'a')
            ->where('a.parent IS NULL')
            ->andWhere('a.startDateTime IS NOT NULL')
            ->setMaxResults(1)
            ->orderBy('a.startDateTime', 'DESC');

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Returns the root album containing the oldest photos
     *
     * @return \Photo\Model\Album
     */
    public function getOldestAlbum() {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Album', 'a')
            ->where('a.parent IS NULL')
            ->andWhere('a.startDateTime IS NOT NULL')
            ->setMaxResults(1)
            ->orderBy('a.startDateTime', 'ASC');

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
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
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Photo\Mapper\Album');
    }

}
