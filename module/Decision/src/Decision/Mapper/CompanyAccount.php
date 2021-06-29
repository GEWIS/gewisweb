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
     * Find all active vacancies of selected company.
     *
     * @param integer $packageID the package id of the company who's active
     * vacancies will be fetched.
     * @param $locale the language
     *
     * @return array Job model.
     */
    public function findActiveVacancies($packageID, $locale)
    {
        $builder = new ResultSetMappingBuilder($this->em);
        $builder->addRootEntityFromClassMetadata('Company\Model\Job', 'j');

        $select = $builder->generateSelectClause(['j' => 't1']);
        $sql = "SELECT $select FROM Job AS t1".
            " WHERE t1.active = 1 AND".
            " t1.package_id = $packageID AND".
            " t1.language = '$locale'";

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
            " WHERE t1.company_id = $id AND t1.packageType = 'job'";

        $query = $this->em->createNativeQuery($sql, $builder);
        return $query->getResult();
    }
}