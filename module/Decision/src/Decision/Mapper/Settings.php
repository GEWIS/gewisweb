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
     * Find all available company user information given a company id
     *
     * @param integer $id the id of the company who's user information
     * will be fetched.
     *
     * @return array CompanyUser model
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
     * Find all available company information given a company id
     *
     * @param integer $id the id of the company who's information
     * will be fetched.
     *
     * @return array Company model
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
     * Find all available company package information given a company id
     *
     * @param integer $id the id of the company who's company information
     * will be fetched.
     *
     * @return array CompanyJobPackage model
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
     * Update the companies information given a number of changed values
     *
     * @param string $collumns the columns in Company table that will be altered
     *
     * @param string $values the new values for the to be altered collumns
     *
     * @param integer $id the id of the company who's company information
     * will be altered.
     *
     * @return null
     */
    public function setCompanyData($collumns, $values, $id){
        //update Company table
        $qb = $this->em->createQueryBuilder();
        $qb->update("Company\Model\Company", "c");
        $qb->where("c.id = $id");
        for($i = 0; $i < count($collumns); $i++){
            $qb->set("c.$collumns[$i]", ":$collumns[$i]");
            $qb->setParameter("$collumns[$i]", "$values[$i]");
        }

        $qb->getQuery()->getResult();

        //If the contact email has changed, also update the CompanyUser table
        if(in_array("contactEmail", $collumns)) {
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
