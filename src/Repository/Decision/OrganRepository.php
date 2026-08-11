<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\Enums\OrganTypes;
use App\Entity\Decision\Organ;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

use function array_map;
use function mb_strtolower;
use function trim;

/**
 * @extends ServiceEntityRepository<Organ>
 */
class OrganRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Organ::class,
        );
    }

    /**
     * Find all active organs.
     *
     * @return Organ[]
     */
    public function findActive(?OrganTypes $type = null): array
    {
        $qb = $this->createQueryBuilder('o');
        $qb->where($qb->expr()->orX(
            $qb->expr()->isNull('o.abrogationDate'),
            $qb->expr()->gt(
                'o.abrogationDate',
                ':now',
            ),
        ))
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            );

        if (null !== $type) {
            // A backed enum is converted to its value by Doctrine; naming the enum class as the parameter type would
            // ask DBAL for a column type by that name, which there is none.
            $qb->andWhere('o.type = :type')
                ->setParameter(
                    'type',
                    $type,
                );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * The ids of the bodies of one kind that an overview lists, in alphabetical order. Ids rather than entities so the
     * overview can shuffle or page them before hydrating only the ones it shows.
     *
     * @return int[]
     */
    public function findOverviewIds(
        OrganTypes $type,
        bool $abrogated,
        string $search = '',
    ): array {
        $qb = $this->createQueryBuilder('o')
            ->select('o.id')
            ->where('o.type = :type')
            ->setParameter(
                'type',
                $type,
            )
            ->orderBy(
                'o.abbr',
                'ASC',
            );

        $now = new DateTime();
        if ($abrogated) {
            $qb->andWhere('o.abrogationDate IS NOT NULL')
                ->andWhere('o.abrogationDate <= :now')
                ->setParameter(
                    'now',
                    $now,
                    Types::DATETIME_MUTABLE,
                );
        } else {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('o.abrogationDate'),
                $qb->expr()->gt(
                    'o.abrogationDate',
                    ':now',
                ),
            ))
                ->setParameter(
                    'now',
                    $now,
                    Types::DATETIME_MUTABLE,
                );
        }

        $search = trim($search);
        if ('' !== $search) {
            $qb->andWhere($qb->expr()->orX(
                'LOWER(o.abbr) LIKE :search',
                'LOWER(o.name) LIKE :search',
            ))
                ->setParameter(
                    'search',
                    '%' . mb_strtolower($search) . '%',
                );
        }

        return array_map(
            static fn (array $row): int => (int) $row['id'],
            $qb->getQuery()->getScalarResult(),
        );
    }

    /**
     * Hydrate the given bodies, in the order they are asked for, with the page each one is shown by. Ids that no longer
     * resolve to a body are left out.
     *
     * @param int[] $ids
     *
     * @return Organ[]
     */
    public function findOverviewByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $organs = $this->createQueryBuilder(
            'o',
            'o.id',
        )
            ->addSelect(
                'i',
                'lr',
                'sd',
                'sl',
            )
            ->leftJoin(
                'o.organInformation',
                'i',
            )
            ->leftJoin(
                'i.liveRevision',
                'lr',
            )
            ->leftJoin(
                'lr.shortDescription',
                'sd',
            )
            ->leftJoin(
                'lr.socialLinks',
                'sl',
            )
            ->where('o.id IN (:ids)')
            ->setParameter(
                'ids',
                $ids,
            )
            ->getQuery()
            ->getResult();

        $ordered = [];
        foreach ($ids as $id) {
            if (!isset($organs[$id])) {
                continue;
            }

            $ordered[] = $organs[$id];
        }

        return $ordered;
    }

    /**
     * Every body that has gone by this abbreviation, newest first. An abbreviation is reused: a committee is
     * abrogated and years later another one is founded under the same letters, and both have a page of their own.
     *
     * @return Organ[]
     */
    public function findAllByAbbr(
        string $abbr,
        ?OrganTypes $type = null,
    ): array {
        $qb = $this->createQueryBuilder('o')
            ->where('o.abbr = :abbr')
            ->setParameter(
                'abbr',
                $abbr,
            )
            ->orderBy(
                'o.foundationDate',
                'DESC',
            );

        if (null !== $type) {
            // A backed enum is converted to its value by Doctrine; naming the enum class as the parameter type would
            // ask DBAL for a column type by that name, which there is none.
            $qb->andWhere('o.type = :type')
                ->setParameter(
                    'type',
                    $type,
                );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Eagerly loads what the administrative overview reads off each body: its page, the revision being worked on, the
     * one before that and the one visitors see. Without this every row lazy-loads four associations of its own, which
     * for the board, who sees every body there is, runs into the hundreds of queries.
     *
     * The result is discarded; hydrating it fills the associations on the bodies that were passed in.
     *
     * @param Organ[] $organs
     */
    public function warmPageAssociations(array $organs): void
    {
        if ([] === $organs) {
            return;
        }

        $this->createQueryBuilder('o')
            ->addSelect(
                'i',
                'current',
                'previous',
                'live',
            )
            ->leftJoin(
                'o.organInformation',
                'i',
            )
            ->leftJoin(
                'i.currentRevision',
                'current',
            )
            ->leftJoin(
                'current.previousRevision',
                'previous',
            )
            ->leftJoin(
                'i.liveRevision',
                'live',
            )
            ->where('o IN (:organs)')
            ->setParameter(
                'organs',
                $organs,
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if an organ with id `$id` is not abrogated.
     */
    public function findActiveById(int $id): ?Organ
    {
        $qb = $this->createQueryBuilder('o');
        $qb->where('o.id = :id')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('o.abrogationDate'),
                $qb->expr()->gt(
                    'o.abrogationDate',
                    ':now',
                ),
            ));

        $qb->setParameter(
            'id',
            $id,
        )
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find all abrogated organs.
     *
     * @return Organ[]
     */
    public function findAbrogated(?OrganTypes $type = null): array
    {
        $qb = $this->createQueryBuilder('o');
        $qb->where($qb->expr()->andX(
            $qb->expr()->isNotNull('o.abrogationDate'),
            $qb->expr()->lte(
                'o.abrogationDate',
                ':now',
            ),
        ))
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            )
            ->orderBy(
                'o.abrogationDate',
                'DESC',
            );

        if (null !== $type) {
            // A backed enum is converted to its value by Doctrine; naming the enum class as the parameter type would
            // ask DBAL for a column type by that name, which there is none.
            $qb->andWhere('o.type = :type')
                ->setParameter(
                    'type',
                    $type,
                );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find an organ with all information.
     *
     * @throws NonUniqueResultException
     */
    public function findOrgan(int $id): ?Organ
    {
        $qb = $this->createQueryBuilder('o');
        $qb->addSelect('om, m')
            ->leftJoin(
                'o.members',
                'om',
            )
            ->leftJoin(
                'om.member',
                'm',
            )
            ->where('o.id = :id');

        $qb->setParameter(
            'id',
            $id,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find an organ by its abbreviation.
     *
     * It is possible that multiple organs with the same abbreviation exist,
     * for example, through the reinstatement of a previously abrogated organ.
     * To retrieve the latest occurrence of such an organ use `$latest`.
     *
     * @param bool $latest Whether to retrieve the latest occurrence of an organ or not
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findByAbbr(
        string $abbr,
        bool $latest,
        ?OrganTypes $type = null,
    ): ?Organ {
        $qb = $this->createQueryBuilder('o');
        $qb->select('o, om, m')
            ->leftJoin(
                'o.members',
                'om',
            )
            ->leftJoin(
                'om.member',
                'm',
            )
            ->where('o.abbr = :abbr')
            ->setParameter(
                'abbr',
                $abbr,
            );

        if (null !== $type) {
            // A backed enum is converted to its value by Doctrine; naming the enum class as the parameter type would
            // ask DBAL for a column type by that name, which there is none.
            $qb->andWhere('o.type = :type')
                ->setParameter(
                    'type',
                    $type,
                );
        }

        if ($latest) {
            $qb->orderBy(
                'o.foundationDate',
                'DESC',
            );
            $queryResult = $qb->getQuery()->getResult();

            if ([] === $queryResult) {
                // the query did not return any records
                return null;
            }

            // the query returned at least 1 record, use first (= latest) record
            return $queryResult[0];
        }

        return $qb->getQuery()->getSingleResult();
    }
}
