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
            $qb->andWhere('o.type = :type')
                ->setParameter(
                    'type',
                    $type,
                    OrganTypes::class,
                );
        }

        return $qb->getQuery()->getResult();
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
            $qb->andWhere('o.type = :type')
                ->setParameter(
                    'type',
                    $type,
                    OrganTypes::class,
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
            $qb->andWhere('o.type = :type')
                ->setParameter(
                    'type',
                    $type,
                    OrganTypes::class,
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
