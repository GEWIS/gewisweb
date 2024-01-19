<?php

declare(strict_types=1);

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
use DateTime;
use Photo\Model\Album as AlbumModel;

/**
 * Mappers for Album.
 *
 * @template-extends BaseMapper<AlbumModel>
 */
class Album extends BaseMapper
{
    /**
     * Returns all the subalbums of a given album.
     *
     * @param AlbumModel $parent     the parent album to retrieve the subalbum from
     * @param int        $start      the result to start at
     * @param int|null   $maxResults max amount of results to return, null for infinite
     *
     * @return AlbumModel[]
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

        if (null !== $maxResults) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * return all the sub-albums without a parent.
     *
     * @return AlbumModel[]
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
     * @return AlbumModel[]
     */
    public function getAlbumsInDateRange(
        DateTime $start,
        DateTime $end,
        bool $onlyPublished = true,
    ): array {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.parent IS NULL')
            ->andWhere('a.startDateTime BETWEEN ?1 AND ?2')
            ->setParameter(1, $start)
            ->setParameter(2, $end)
            ->orderBy('a.startDateTime', 'DESC');

        if ($onlyPublished) {
            $qb->andWhere('a.published = TRUE');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieves all root albums which do not have a startDateTime specified.
     * This is in most cases analogous to returning all empty albums.
     *
     * @return AlbumModel[]
     */
    public function getAlbumsWithoutDate(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.startDateTime IS NULL');

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the root album containing the most recent photos.
     */
    public function getNewestAlbum(bool $onlyPublished = true): ?AlbumModel
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.parent IS NULL')
            ->andWhere('a.startDateTime IS NOT NULL')
            ->setMaxResults(1)
            ->orderBy('a.startDateTime', 'DESC');

        if ($onlyPublished) {
            $qb->andWhere('a.published = TRUE');
        }

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Returns the root album containing the oldest photos.
     */
    public function getOldestAlbum(bool $onlyPublished = true): ?AlbumModel
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.parent IS NULL')
            ->andWhere('a.startDateTime IS NOT NULL')
            ->setMaxResults(1)
            ->orderBy('a.startDateTime', 'ASC');

        if ($onlyPublished) {
            $qb->andWhere('a.published = TRUE');
        }

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    protected function getRepositoryName(): string
    {
        return AlbumModel::class;
    }
}
