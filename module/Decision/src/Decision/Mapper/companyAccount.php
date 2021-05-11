<?php

namespace Decision\Mapper;
use Decision\Model\comanyAccount as companyAccountModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMappingBuilder;




class companyAccount
{

    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function findactiveVacancies()
    {
        // unfortunately, there is no support for functions like DAY() and MONTH()
        // in doctrine2, thus we have to use the NativeSQL here
        $builder = new ResultSetMappingBuilder($this->em);
        $builder->addRootEntityFromClassMetadata('Decision\Model\companyAccount', 'm');

        $select = $builder->generateSelectClause(['m' => 't1']);
        $sql = "SELECT $select FROM Job AS t1";

        $query = $this->em->createNativeQuery($sql, $builder);
        //$query->setParameter('name');
        return $query->getResult();
    }
}
