<?php

namespace User\Mapper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class Session
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

    public function find($id, $secret)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('s')
            ->from('User\Model\Session', 's')
            ->where('s.id = ?1')
            ->andWhere('s.secret = ?2');
        $qb->setParameter(1, $id)
            ->setParameter(2, $secret);

        return $qb->getQuery()->getOneOrNullResult();
    }
    /**
     * Find a session by its id
     *
     * @param string $id
     *
     * @return Session
     */
    public function findById($id)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('s')
            ->from('User\Model\Session', 's')
            ->where('s.id = ?1');
        $qb->setParameter(1, $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function removeById($id)
    {
        $session = $this->findById($id);
        if (!is_null($session)) {
            $this->em->remove($session);
            $this->em->flush();
        }
    }


    /**
     * Persist a session model
     */
    public function persist($session)
    {
        $this->em->persist($session);
        $this->em->flush();
    }

    /**
     * Detach an entity from the entity manager.
     *
     * @param $entity
     */
    public function detach($entity)
    {
        $this->em->detach($entity);
    }

    /**
     * Flush the entity manager
     */
    public function flush()
    {
        $this->em->flush();
    }

    /**
     * Remove an entity
     *
     * @param $entity
     */
    public function remove($entity)
    {
        $this->em->remove($entity);
    }
    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('User\Model\Session');
    }
}
