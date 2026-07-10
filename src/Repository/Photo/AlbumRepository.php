<?php

declare(strict_types=1);

namespace App\Repository\Photo;

use App\Entity\Photo\Album;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

use function addcslashes;
use function array_filter;
use function array_merge;
use function array_values;
use function in_array;

/**
 * @extends ServiceEntityRepository<Album>
 */
class AlbumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Album::class,
        );
    }

    /**
     * Gets all root albums with a start date between the specified dates.
     *
     * @param DateTime $start start date and time
     * @param DateTime $end   end date and time
     *
     * @return Album[]
     */
    public function getAlbumsInDateRange(
        DateTime $start,
        DateTime $end,
        bool $onlyPublished = true,
    ): array {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.parent IS NULL')
            ->andWhere('a.startDateTime BETWEEN :start AND :end')
            ->setParameter(
                'start',
                $start,
                Types::DATETIME_MUTABLE,
            )
            ->setParameter(
                'end',
                $end,
                Types::DATETIME_MUTABLE,
            )
            ->orderBy(
                'a.startDateTime',
                'DESC',
            );

        if ($onlyPublished) {
            $qb->andWhere('a.published = TRUE');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieves all root albums which do not have a startDateTime specified.
     * This is in most cases analogous to returning all empty albums.
     *
     * @return Album[]
     */
    public function getAlbumsWithoutDate(): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.startDateTime IS NULL');

        return $qb->getQuery()->getResult();
    }

    /**
     * Gets all root albums based on their name.
     *
     * Only returns published albums and grouping has to happen downstream at the consumers.
     *
     * @return Album[]
     */
    public function search(string $query): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.parent IS NULL')
            ->andWhere('a.published = TRUE')
            ->andWhere('a.startDateTime IS NOT NULL')
            ->andWhere('a.endDateTime IS NOT NULL')
            ->andWhere('a.name LIKE :query')
            ->orderBy(
                'a.startDateTime',
                'DESC',
            );

        $qb->setParameter(
            'query',
            '%' . addcslashes(
                $query,
                '%_',
            ) . '%',
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the root album containing the most recent photos.
     */
    public function getNewestAlbum(bool $onlyPublished = true): ?Album
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.parent IS NULL')
            ->andWhere('a.startDateTime IS NOT NULL')
            ->setMaxResults(1)
            ->orderBy(
                'a.startDateTime',
                'DESC',
            );

        if ($onlyPublished) {
            $qb->andWhere('a.published = TRUE');
        }

        $res = $qb->getQuery()->getResult();

        return [] === $res
            ? null
            : $res[0];
    }

    /**
     * Returns the root album containing the oldest photos.
     */
    public function getOldestAlbum(bool $onlyPublished = true): ?Album
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.parent IS NULL')
            ->andWhere('a.startDateTime IS NOT NULL')
            ->setMaxResults(1)
            ->orderBy(
                'a.startDateTime',
                'ASC',
            );

        if ($onlyPublished) {
            $qb->andWhere('a.published = TRUE');
        }

        $res = $qb->getQuery()->getResult();

        return [] === $res
            ? null
            : $res[0];
    }

    /**
     * All root (top-level) albums, most recent first, for the board admin overview. Undated albums sort last (MariaDB
     * orders NULL dates after any value on a descending sort), and both published and unpublished albums are returned.
     *
     * @return Album[]
     */
    public function findRootAlbums(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.parent IS NULL')
            ->orderBy(
                'a.startDateTime',
                'DESC',
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * The albums that may be chosen as a parent for the given album (all of them when creating). The album itself and
     * its descendants are excluded, so an album can never be made its own ancestor.
     *
     * @return Album[]
     */
    public function findAssignableParents(?Album $exclude): array
    {
        $albums = $this->findBy(
            [],
            ['name' => 'ASC'],
        );

        if (null === $exclude) {
            return $albums;
        }

        $excludedIds = $this->subtreeIds($exclude);

        return array_values(array_filter(
            $albums,
            static fn (Album $album): bool => !in_array(
                $album->getId(),
                $excludedIds,
                true,
            ),
        ));
    }

    /**
     * @return list<int>
     */
    private function subtreeIds(Album $album): array
    {
        $ids = [(int) $album->getId()];
        foreach ($album->getChildren() as $child) {
            $ids = array_merge(
                $ids,
                $this->subtreeIds($child),
            );
        }

        return $ids;
    }
}
