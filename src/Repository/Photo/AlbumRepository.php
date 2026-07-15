<?php

declare(strict_types=1);

namespace App\Repository\Photo;

use App\Entity\Photo\Album;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

use function addcslashes;

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
     * Albums matching a name fragment, for the admin move-photos picker. Unlike {@see self::search} (the public
     * gallery) this is not restricted to published, dated, root albums: a photo can be moved into any album, drafts
     * and sub-albums included. The parent is fetch-joined for a disambiguating label, and the result is capped so the
     * typeahead stays light.
     *
     * @return Album[]
     */
    public function searchForMove(
        string $query,
        int $limit = 25,
    ): array {
        return $this->createQueryBuilder('a')
            ->leftJoin(
                'a.parent',
                'parent',
            )
            ->addSelect('parent')
            ->where('a.name LIKE :query')
            ->setParameter(
                'query',
                '%' . addcslashes(
                    $query,
                    '%_',
                ) . '%',
            )
            ->orderBy(
                'a.name',
                'ASC',
            )
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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
     * The number of direct sub-albums each of the given albums has, keyed by album id, in a single query — so a grid of
     * album cards does not issue one `COUNT(*) ... WHERE parent_id = ?` per card. Albums with no sub-albums are absent.
     *
     * @param Album[] $albums
     *
     * @return array<int, int>
     */
    public function getSubAlbumCounts(array $albums): array
    {
        if ([] === $albums) {
            return [];
        }

        $counts = [];
        foreach (
            $this->createQueryBuilder('a')
                ->select(
                    'IDENTITY(a.parent) AS parentId',
                    'COUNT(a.id) AS total',
                )
                ->where('a.parent IN (:parents)')
                ->setParameter(
                    'parents',
                    $albums,
                )
                ->groupBy('a.parent')
                ->getQuery()
                ->getScalarResult() as $row
        ) {
            $counts[(int) $row['parentId']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * The album whose generated cover path ends with the given filename, used to resolve a legacy `/data/{2ch}/{file}`
     * URL onto the migrated cover (whose path re-roots that same filename under the album).
     */
    public function findOneByCoverBasename(string $basename): ?Album
    {
        return $this->createQueryBuilder('a')
            ->where('a.coverPath LIKE :suffix')
            ->setParameter(
                'suffix',
                '%/' . addcslashes(
                    $basename,
                    '%_',
                ),
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
}
