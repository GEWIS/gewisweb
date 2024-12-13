<?php

declare(strict_types=1);

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
use DateTime;
use Photo\Model\Album as AlbumModel;

use function addcslashes;

/**
 * Mappers for Album.
 *
 * @template-extends BaseMapper<AlbumModel>
 */
class Album extends BaseMapper
{
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
     * Gets all root albums based on their name.
     *
     * Only returns published albums and grouping has to happen downstream at the consumers.
     *
     * @return AlbumModel[]
     */
    public function search(string $query): array
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.parent IS NULL')
            ->andWhere('a.published = TRUE')
            ->andWhere('a.startDateTime IS NOT NULL')
            ->andWhere('a.endDateTime IS NOT NULL')
            ->andWhere('a.name LIKE :query')
            ->orderBy('a.startDateTime', 'DESC');

        $qb->setParameter('query', '%' . addcslashes($query, '%_') . '%');

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
