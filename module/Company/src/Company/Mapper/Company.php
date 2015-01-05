<?php

namespace Company\Mapper;

use Company\Model\Company as CompanyModel;
use Doctrine\ORM\EntityManager;

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
     * @return An array of companies with the given asciiName.
     */
    public function findEditableCompaniesWithAsciiName($asciiName)
    {

        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.asciiName=:asciiCompanyName');
        $qb->setParameter('asciiCompanyName', $asciiName);
        $qb->setMaxResults(1);
        return $qb->getQuery()->getResult();
    }

    public function findCompaniesWithAsciiName($asciiName)
    {

        $result = $this->findEditableCompaniesWithAsciiName($asciiName);
        foreach($results as $company){
            $em->detach($company);
        }
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
