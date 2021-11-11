<?php

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Organ as OrganModel;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

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
     * @param string $type
     *
     * @return array
     */
    public function findActive($type = null)
    {
        $criteria = [
            'abrogationDate' => null,
        ];
        if (!is_null($type)) {
            $criteria['type'] = $type;
        }

        return $this->getRepository()->findBy($criteria);
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
        $criteria = [
            'id' => $id,
            'abrogationDate' => null,
        ];

        return $this->getRepository()->findOneBy($criteria);
    }

    /**
     * Find all abrogated organs.
     *
     * @param string $type
     *
     * @return array
     */
    public function findAbrogated($type = null)
    {
        $qb = $this->getRepository()->createQueryBuilder('o');

        $qb->select('o')
            ->where('o.abrogationDate IS NOT NULL');
        if (!is_null($type)) {
            $qb->andWhere('o.type = :type')
                ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find an organ with all information.
     *
     * @param int $id
     *
     * @return OrganModel
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findOrgan($id)
    {
        $qb = $this->getRepository()->createQueryBuilder('o');

        $qb->select('o, om, m')
            ->leftJoin('o.members', 'om')
            ->leftJoin('om.member', 'm')
            ->where('o.id = :id');

        $qb->setParameter('id', $id);

        return $qb->getQuery()->getSingleResult();
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
     * @param string|null $type
     *
     * @return OrganModel
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findByAbbr(string $abbr, bool $latest, ?string $type = null): OrganModel
    {
        $qb = $this->getRepository()->createQueryBuilder('o');

        $qb->select('o, om, m')
            ->leftJoin('o.members', 'om')
            ->leftJoin('om.member', 'm')
            ->where('o.abbr = :abbr')
            ->setParameter('abbr', $abbr);

        if (!is_null($type)) {
            $qb->andWhere('o.type = :type')
                ->setParameter('type', $type);
        }

        if ($latest) {
            $qb->orderBy('o.foundationDate', 'DESC');
            $queryResult = $qb->getQuery()->getResult();

            if (empty($queryResult)) {
                // the query did not return any records
                throw new NoResultException();
            }

            // the query returned at least 1 record, use first (= latest) record
            return $queryResult[0];
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return OrganModel::class;
    }
}
