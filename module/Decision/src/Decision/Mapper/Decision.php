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
     * Persist a decision model.
     *
     * @param DecisionmberModel $decision Decision to persist.
     */
    public function persist(DecisionModel $decision)
    {
        $this->em->persist($decision);
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
