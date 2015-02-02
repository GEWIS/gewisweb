<?php

namespace Company\Mapper;

use Company\Model\Company as CompanyModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

/**
 * Mappers for companies.
 *
 * NOTE: Companies will be modified externally by a script. Modifycations will be
 * overwritten.
 */

class Company
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
    public function save(){
        $this->em->flush();
    }

    public function insert(){
        $company=new CompanyModel($this->em);
        
        $company->setLanguage('en');
        $company->setHidden(false);
        $this->em->persist($company);
        return $company;
    }
    /**
     * Find all companies.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Find the company with the given asciiName
     *
     * @param asciiName The 'username' of the company to get.
     * @param asObject if yes, returns the company as an object in an array, else returns the company as an array of an array
     * @return An array of companies with the given asciiName.
     */
    public function findEditableCompaniesWithAsciiName($asciiName, $asObject)
    {

        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.asciiName=:asciiCompanyName');
        $qb->setParameter('asciiCompanyName', $asciiName);
        $qb->setMaxResults(1);
        if ($asObject){
            return $qb->getQuery()->getResult();
        }
        else{
            return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        }
    }

    public function findCompaniesWithAsciiName($asciiName)
    {

        $result = $this->findEditableCompaniesWithAsciiName($asciiName,true);
        foreach($results as $company){
            $em->detach($company);
        }
        return $result;
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\Company');
    }
}
