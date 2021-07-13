<?php

namespace Decision\Mapper;

use Decision\Model\MeetingNotes;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class Decision
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
     * Search decisions.
     *
     * @param string $query
     *
     * @return Collection
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
     * @param MeetingNotes to persist
     */
    public function persist($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Decision\Model\Decision');
    }
}
