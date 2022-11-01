<?php

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Enums\OrganTypes;
use Decision\Model\Organ as OrganModel;
use Doctrine\ORM\{
    NoResultException,
    NonUniqueResultException,
};

/**
 * Mappers for organs.
 *
 * NOTE: Organs will be modified externally by a script. Modifycations will be
 * overwritten.
 */
class Organ extends BaseMapper
{
    /**
     * Find all active organs.
     *
     * @param OrganTypes|null $type
     *
     * @return array
     */
    public function findActive(?OrganTypes $type = null): array
    {
        $criteria = [
            'abrogationDate' => null,
        ];

        if (null !== $type) {
            $criteria['type'] = $type;
        }

        return array_filter(
            $this->getRepository()->findBy($criteria),
            function (OrganModel $organ) {
                return $organ->getFoundation()->isValid();
            },
        );
    }

    /**
     * Check if an organ with id `$id` is not abrogated.
     *
     * @param int $id
     *
     * @return OrganModel|null
     */
    public function findActiveById(int $id): ?OrganModel
    {
        $qb = $this->getRepository()->createQueryBuilder('o');
        $qb->where('o.id = :id')
            ->andWhere('o.abrogationDate IS NULL');

        $qb->setParameter('id', $id);
        /** @var OrganModel|null $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        if (null !== $result) {
            return $result->getFoundation()->isValid() ? $result : null;
        }

        return $result;
    }

    /**
     * Find all abrogated organs.
     *
     * @param OrganTypes|null $type
     *
     * @return array
     */
    public function findAbrogated(?OrganTypes $type = null): array
    {
        $qb = $this->getRepository()->createQueryBuilder('o');
        $qb->where('o.abrogationDate IS NOT NULL');

        if (null !== $type) {
            $qb->andWhere('o.type = :type')
                ->setParameter('type', $type);
        }

        return array_filter(
            $qb->getQuery()->getResult(),
            function (OrganModel $organ) {
                return $organ->getFoundation()->isValid();
            },
        );
    }

    /**
     * Find an organ with all information.
     *
     * @param int $id
     *
     * @return OrganModel|null
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

        /** @var OrganModel|null $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        if (null !== $result) {
            return $result->getFoundation()->isValid() ? $result : null;
        }

        return $result;
    }

    /**
     * Find an organ by its abbreviation.
     *
     * It is possible that multiple organs with the same abbreviation exist,
     * for example, through the reinstatement of a previously abrogated organ.
     * To retrieve the latest occurrence of such an organ use `$latest`.
     *
     * @param string $abbr
     * @param bool $latest Whether to retrieve the latest occurrence of an organ or not
     * @param OrganTypes|null $type
     *
     * @return OrganModel|null
     *
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
            return $queryResult[0]->getFoundation()->isValid() ? $queryResult[0] : null;
        }

        /** @var OrganModel|null $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        if (null !== $result) {
            return $result->getFoundation()->isValid() ? $result : null;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return OrganModel::class;
    }
}
