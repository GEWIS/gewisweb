<?php

namespace User\Mapper;

use Doctrine\ORM\EntityManager;

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
     * Get the repository for this mapper.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('User\Model\Session');
    }
}
