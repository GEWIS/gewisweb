<?php

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
use DateTime;
use Photo\Model\Album as AlbumModel;

/**
 * Mappers for Album.
 */
class Album extends BaseMapper
{
    /**
     * Returns all the subalbums of a given album.
     *
     * @param AlbumModel $parent the parent album to retrieve the subalbum from
     * @param int $start the result to start at
     * @param int|null $maxResults max amount of results to return, null for infinite
     *
     * @return array of subalbums or null if there are none
     */
    public function getSubAlbums(
        AlbumModel $parent,
        int $start = 0,
        ?int $maxResults = null,
    ): array {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.parent = ?1')
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
     * @return array
     */
    public function getRootAlbums(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.parent IS NULL')
            ->orderBy('a.startDateTime', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Gets all root albums with a start date between the specified dates.
     *
     * @param DateTime $start start date and time
     * @param DateTime $end   end date and time
     *
     * @return array
     */
    public function getAlbumsInDateRange(
        DateTime $start,
        DateTime $end,
    ): array {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.parent IS NULL')
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
     * @return array
     */
    public function getAlbumsWithoutDate(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.parent IS NULL')
            ->andWhere('a.startDateTime IS NULL');

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the root album containing the most recent photos.
     *
     * @return AlbumModel|null
     */
    public function getNewestAlbum(): ?AlbumModel
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.parent IS NULL')
            ->andWhere('a.startDateTime IS NOT NULL')
            ->setMaxResults(1)
            ->orderBy('a.startDateTime', 'DESC');

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Returns the root album containing the oldest photos.
     *
     * @return AlbumModel|null
     */
    public function getOldestAlbum(): ?AlbumModel
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.parent IS NULL')
            ->andWhere('a.startDateTime IS NOT NULL')
            ->setMaxResults(1)
            ->orderBy('a.startDateTime', 'ASC');

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return AlbumModel::class;
    }
}
