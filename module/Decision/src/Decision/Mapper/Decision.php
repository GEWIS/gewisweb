<?php

namespace Decision\Mapper;

use Decision\Model\Decision as DecisionModel;
use Doctrine\ORM\EntityManager;

class Decision
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
     * Search decisions.
     *
     * @param string $query
     *
     * @return array
     */
    public function search($query)
    {
        $qb = $this->getRepository()->createQueryBuilder('d');

        $qb->select('d, m')
            ->where('d.content LIKE :query')
            ->join('d.meeting', 'm')
            ->orderBy('m.date', 'DESC')
            ->setMaxResults(50);

        $qb->setParameter('query', "%$query%");

        return $qb->getQuery()->getResult();
    }

    /**
     * Persist an entity.
     *
     * @param $entity to persist.
     */
    public function persist($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Decision\Model\Decision');
    }
}
