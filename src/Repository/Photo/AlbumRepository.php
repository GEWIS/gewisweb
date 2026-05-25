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
}
