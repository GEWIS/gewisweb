<?php

namespace Activity\Mapper;

use User\Model\Activity as ActivityModel;
use Doctrine\ORM\EntityManager;

class Activity
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

    public function hello() {
        print_r($this);
    }
}
