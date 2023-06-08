<?php

declare(strict_types=1);

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Enums\OrganTypes;
use Decision\Model\Organ as OrganModel;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * Mappers for organs.
 *
 * NOTE: Organs will be modified externally by a script. Modifications will be overwritten.
 *
 * @template-extends BaseMapper<OrganModel>
 */
class Organ extends BaseMapper
{
    /**
     * Find all active organs.
     *
     * @return array<array-key, OrganModel>
     */
    public function findActive(?OrganTypes $type = null): array
    {
        $criteria = [
            'abrogationDate' => null,
        ];

        if (null !== $type) {
            $criteria['type'] = $type;
        }

        return $this->getRepository()->findBy($criteria);
    }

    /**
     * Check if an organ with id `$id` is not abrogated.
     */
    public function findActiveById(int $id): ?OrganModel
    {
        return $this->getRepository()->findOneBy(
            [
                'id' => $id,
                'abrogationDate' => null,
            ],
        );
    }

    /**
     * Find all abrogated organs.
     *
     * @return array<array-key, OrganModel>
     */
    public function findAbrogated(?OrganTypes $type = null): array
    {
        $qb = $this->getRepository()->createQueryBuilder('o');
        $qb->where('o.abrogationDate IS NOT NULL');

        if (null !== $type) {
            $qb->andWhere('o.type = :type')
                ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find an organ with all information.
     *
     * @throws NonUniqueResultException
     */
    public function findOrgan(int $id): ?OrganModel
    {
        $qb = $this->getRepository()->createQueryBuilder('o');
        $qb->select('o, om, m')
            ->leftJoin('o.members', 'om')
            ->leftJoin('om.member', 'm')
            ->where('o.id = :id');

        $qb->setParameter('id', $id);

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
    ): ?OrganModel {
        $qb = $this->getRepository()->createQueryBuilder('o');
        $qb->select('o, om, m')
            ->leftJoin('o.members', 'om')
            ->leftJoin('om.member', 'm')
            ->where('o.abbr = :abbr')
            ->setParameter('abbr', $abbr);

        if (null !== $type) {
            $qb->andWhere('o.type = :type')
                ->setParameter('type', $type);
        }

        if ($latest) {
            $qb->orderBy('o.foundationDate', 'DESC');
            $queryResult = $qb->getQuery()->getResult();

            if (empty($queryResult)) {
                // the query did not return any records
                return null;
            }

            // the query returned at least 1 record, use first (= latest) record
            return $queryResult[0];
        }

        return $qb->getQuery()->getSingleResult();
    }

    protected function getRepositoryName(): string
    {
        return OrganModel::class;
    }
}
