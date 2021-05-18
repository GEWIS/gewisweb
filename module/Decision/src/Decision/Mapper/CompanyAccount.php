<?php

namespace Decision\Mapper;
use Decision\Model\Vacancy as vacancy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMappingBuilder;




class companyAccount
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
     * Find all active vacancies of selected company
     *
     * @param string $cName the name of the company who's active
     * vacancies will be fetched.
     *
     * @return array Name and description of active vacancies.
     */
    public function findactiveVacancies($cName)
    {
        $builder = new ResultSetMappingBuilder($this->em);
        $builder->addRootEntityFromClassMetadata('Decision\Model\Vacancy', 'v');

        $select = $builder->generateSelectClause(['v' => 't1']);
        $sql = "SELECT $select FROM Job AS t1".
        " WHERE t1.active = 1 AND".
        " t1.companyName = '$cName'";

        $query = $this->em->createNativeQuery($sql, $builder);
        return $query->getResult();
    }
}
