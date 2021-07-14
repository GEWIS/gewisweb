<?php

namespace Photo\Mapper;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Photo\Model\Album as AlbumModel;

/**
 * Mappers for Album.
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
     * Constructor.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Retrieves an album by id from the database.
     *
     * @param int $albumId the id of the album
     *
     * @return AlbumModel|null
     */
    public function getAlbumById($albumId)
    {
        return $this->getRepository()->find($albumId);
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Photo\Model\Album');
    }

    /**
     * Returns all the subalbums of a given album.
     *
     * @param AlbumModel $parent the parent album to retrieve the
     *                               subalbum from
     * @param int $start the result to start at
     * @param int $maxResults max amount of results to return,
     *                               null for infinite
     *
     * @return Collection of subalbums or null if there are none
     */
    public function getSubAlbums($parent, $start = 0, $maxResults = null)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Album', 'a')
            ->where('a.parent = ?1')
            ->setParameter(1, $parent)
            ->setFirstResult($start)
            ->orderBy('a.startDateTime', 'ASC');
        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * return all the sub-albums without a parent.
     *
     * @return Collection of \Photo\Model\Album
     */
    public function getRootAlbums()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Album', 'a')
            ->where('a.parent IS NULL')
            ->orderBy('a.startDateTime', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Gets all root albums with a start date between the specified dates.
     *
     * @param DateTime $start start date and time
     * @param DateTime $end   end date and time
     *
     * @return Collection of \Photo\Model\Album
     */
    public function getAlbumsInDateRange($start, $end)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Album', 'a')
            ->where('a.parent IS NULL')
            ->andWhere('a.startDateTime BETWEEN ?1 AND ?2')
            ->setParameter(1, $start)
            ->setParameter(2, $end)
            ->orderBy('a.startDateTime', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieves all root albums which do not have a startDateTime specified.
     * This is in most cases analogous to returning all empty albums.
     *
     * @return Collection
     */
    public function getAlbumsWithoutDate()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Album', 'a')
            ->where('a.parent IS NULL')
            ->andWhere('a.startDateTime IS NULL');

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the root album containing the most recent photos.
     *
     * @return AlbumModel
     */
    public function getNewestAlbum()
    {
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
     * Returns the root album containing the oldest photos.
     *
     * @return AlbumModel
     */
    public function getOldestAlbum()
    {
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
     * Removes an album.
     */
    public function remove(AlbumModel $album)
    {
        $this->em->remove($album);
    }

    /**
     * Persist album.
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
}
