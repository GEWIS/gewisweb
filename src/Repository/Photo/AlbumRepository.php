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
use function array_values;

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

        // Every album is loaded, so exclude the album and its descendants by walking each candidate's parent chain
        // (getId() on a parent proxy needs no query) against this id map, rather than iterating the lazy children
        // collections (which would fire one SELECT per subtree node).
        $byId = [];
        foreach ($albums as $album) {
            $byId[(int) $album->getId()] = $album;
        }

        $excludeId = (int) $exclude->getId();

        return array_values(array_filter(
            $albums,
            fn (Album $album): bool => !$this->descendsFromOrIs(
                $album,
                $excludeId,
                $byId,
            ),
        ));
    }

    /**
     * Whether $album is $ancestorId or has it somewhere up its parent chain.
     *
     * @param array<int, Album> $byId
     */
    private function descendsFromOrIs(
        Album $album,
        int $ancestorId,
        array $byId,
    ): bool {
        for ($current = $album; null !== $current;) {
            if ((int) $current->getId() === $ancestorId) {
                return true;
            }

            $parent = $current->getParent();
            $current = null === $parent
                ? null
                : ($byId[(int) $parent->getId()] ?? null);
        }

        return false;
    }
}
