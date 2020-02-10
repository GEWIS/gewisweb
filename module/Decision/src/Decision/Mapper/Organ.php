<?php

namespace Decision\Mapper;

use Decision\Model\Organ as OrganModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for organs.
 *
 * NOTE: Organs will be modified externally by a script. Modifycations will be
 * overwritten.
 */
class Organ
{

    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;


    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

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
            'abrogationDate' => null
        ];
        if (!is_null($type)) {
            $criteria['type'] = $type;
        }
        return $this->getRepository()->findBy($criteria);
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
     * Find all organs.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Find an organ with all information.
     *
     * @param int $id
     *
     * @return Decision\Model\Organ
     */
    public function find($id)
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
     * Find an organ by its abbreviation
     *
     * It is possible that multiple organs with the same abbreviation exist,
     * for example, through the reinstatement of an previously abrogated organ.
     * To retrieve the latest occurence of such an organ use `$latest`. 
     *
     * @param string $abbr
     * @param string $type
     * @param bool $latest
     *    Whether to retrieve the latest occurence of an organ or not.
     *
     * @return \Decision\Model\Organ
     */
    public function findByAbbr($abbr, $type = null, $latest = false)
    {
        $qb = $this->getRepository()->createQueryBuilder('o');

        $qb->select('o, om, m')
            ->leftJoin('o.members', 'om')
            ->leftJoin('om.member', 'm')
            ->where('o.abbr = :abbr');
        if (!is_null($type)) {
            $qb->andWhere('o.type = :type')
                ->setParameter('type', $type);
        }
        if ($latest) {
            $qb->orderBy('o.foundationDate', 'DESC')
                ->setMaxResults(1);
        }

        $qb->setParameter('abbr', $abbr);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Decision\Model\Organ');
    }
}
