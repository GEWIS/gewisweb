<?php

namespace Decision\Mapper;
use Decision\Model\CompanyInfo as companyinfo;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMappingBuilder;




class Settings
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
     * Find all available company information
     *
     * @param string $cName the name of the company who's information
     * will be fetched.
     *
     * @return array Information of company
     */
    public function findCompanyUser($id)
    {
        $builder = new ResultSetMappingBuilder($this->em);
        $builder->addRootEntityFromClassMetadata('User\Model\CompanyUser', 'cu');

        $select = $builder->generateSelectClause(['cu' => 't1']);
        $sql = "SELECT $select FROM CompanyUser AS t1".
            " WHERE t1.id = $id";

        $query = $this->em->createNativeQuery($sql, $builder);
        return $query->getResult();
    }

    /**
     * Find all available company information
     *
     * @param string $cName the name of the company who's information
     * will be fetched.
     *
     * @return array Information of company
     */
    public function findCompanyInfo($id)
    {
        $builder = new ResultSetMappingBuilder($this->em);
        $builder->addRootEntityFromClassMetadata('Company\Model\Company', 'ci');

        $select = $builder->generateSelectClause(['ci' => 't1']);
        $sql = "SELECT $select FROM Company AS t1".
        " WHERE t1.id = '$id'";

        $query = $this->em->createNativeQuery($sql, $builder);
        return $query->getResult();
    }

    /**
     * Find all available company package information
     *
     * @param string $cName the name of the company who's package information
     * will be fetched.
     *
     * @return array package Information of company
     */
    public function findCompanyPackageInfo($id)
    {
        $builder = new ResultSetMappingBuilder($this->em);
        $builder->addRootEntityFromClassMetadata('Company\Model\CompanyJobPackage', 'cp');

        $select = $builder->generateSelectClause(['cp' => 't1']);
        $sql = "SELECT $select FROM CompanyPackage AS t1".
            " WHERE t1.company_id = $id";

        $query = $this->em->createNativeQuery($sql, $builder);
        return $query->getResult();
    }


    /**
     * Update the database
     *
     * @param string $cName the name of the company who's package information
     * will be fetched.
     *
     * @return array package Information of company
     */
    public function setCompanyData($collumns, $values, $id){

        //TODO sql injection protection

        $qb = $this->em->createQueryBuilder();
        $qb->update("Company\Model\Company", "c");
        $qb->where("c.id = $id");
        for($i = 0; $i < count($collumns); $i++){
            $qb->set("c.$collumns[$i]", ":$collumns[$i]");
            $qb->setParameter("$collumns[$i]", "$values[$i]");
        }

        $qb->getQuery()->getResult();

        if(in_array("email", $collumns)) {
            $qb = $this->em->createQueryBuilder();
            $qb->update("User\Model\CompanyUser", "c");
            $qb->where("c.id = $id");

            $i = array_search("email", $collumns);
            $qb->set("c.contactEmail", ":contactEmail");
            $qb->setParameter("contactEmail", "$values[$i]");
            $qb->getQuery()->getResult();
        }
    }
}
