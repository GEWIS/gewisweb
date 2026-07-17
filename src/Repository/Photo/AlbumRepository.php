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
     * The start date of every published, dated root album, in one query, so the overview can derive the association
     * years that actually hold albums (a year with none never reaches the year switcher).
     *
     * @return list<array{startDateTime: DateTime}>
     */
    public function getPublishedRootAlbumStartDates(): array
    {
        /** @var list<array{startDateTime: DateTime}> $rows */
        $rows = $this->createQueryBuilder('a')
            ->select('a.startDateTime')
            ->where('a.parent IS NULL')
            ->andWhere('a.startDateTime IS NOT NULL')
            ->andWhere('a.published = TRUE')
            ->getQuery()
            ->getResult();

        return $rows;
    }

    /**
     * The number of direct sub-albums each of the given albums has, keyed by album id, in a single query — so a grid of
     * album cards does not issue one `COUNT(*) ... WHERE parent_id = ?` per card. Albums with no sub-albums are absent.
     * Public overviews pass `$publishedOnly` so a draft sub-album is not counted against a published parent.
     *
     * @param Album[] $albums
     *
     * @return array<int, int>
     */
    public function getSubAlbumCounts(
        array $albums,
        bool $publishedOnly = false,
    ): array {
        if ([] === $albums) {
            return [];
        }

        $qb = $this->createQueryBuilder('a')
            ->select(
                'IDENTITY(a.parent) AS parentId',
                'COUNT(a.id) AS total',
            )
            ->where('a.parent IN (:parents)')
            ->setParameter(
                'parents',
                $albums,
            )
            ->groupBy('a.parent');

        if ($publishedOnly) {
            $qb->andWhere('a.published = TRUE');
        }

        $counts = [];
        foreach ($qb->getQuery()->getScalarResult() as $row) {
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
