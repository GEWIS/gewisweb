<?php

namespace Activity\Mapper;

use Doctrine\ORM\EntityManager;

class ActivityCategory
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Get a Category by an Id.
     *
     * @param $id
     *
     * @return array
     */
    public function getCategoryById($id)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivityCategory', 'a')
            ->where('a.id = :id')
            ->setParameter('id', $id);
        $result = $qb->getQuery()->getResult();

        return count($result) > 0 ? $result[0] : null;
    }

    /**
     * Get all Categories.
     *
     * @return array
     */
    public function getAllCategories()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivityCategory', 'a');

        return $qb->getQuery()->getResult();
    }
}
