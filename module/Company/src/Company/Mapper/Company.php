<?php

namespace Company\Mapper;

use Company\Model\Company as CompanyModel;
use Company\Model\CompanyI18n;
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
        $company = new CompanyModel($this->em);

        $companiesWithSameSlugName = $this->findEditableCompaniesWithSlugName($company->getSlugName(), false);
        
        // Only for testing, logo will be implemented in a later issue, and it will be validated before it comes here, so this will never be called in production code. TODO: remove this when implemented logo and logo validation
        
        
        // TODO: implement language
        //if($company->getLanguage == null){
        //    $company->setLanguage("en");
        //}
        if(empty($companiesWithSameSlugName)){
            // We have a problem, ID is not set, so we set a placeholder. When the id is known, we change this into the real id. 
            $company->setLanguageNeutralId(-1);
        } else {
            $company->setLanguageNeutralId($companiesWithSameSlugName[0]->getLanguageNeutralId());
        }

        // TODO: make this more dynamic
        $en = new CompanyI18n();
        $en->setLanguage("en");
        $en->setCompany($company);
        if($en->getLogo() == null){
            $en->setLogo("");
        }
        $this->em->persist($en);
        $company->addTranslation($en);
        $nl = new CompanyI18n();
        $nl->setLanguage("nl");
        $nl->setCompany($company);
        if($nl->getLogo() == null){
            $nl->setLogo("");
        }
        $this->em->persist($nl);
        $company->addTranslation($nl);

        $company->setHidden(false);
        $this->em->persist($company);
        echo $company->getId();
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
     * Find the company with the given slugName
     *
     * @param slugName The 'username' of the company to get.
     * @param asObject if yes, returns the company as an object in an array, else returns the company as an array of an array
     * @return An array of companies with the given slugName.
     */
    public function findEditableCompaniesWithSlugName($slugName, $asObject)
    {

        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.slugName=:slugCompanyName');
        $qb->setParameter('slugCompanyName', $slugName);
        $qb->setMaxResults(1);
        if ($asObject){
            return $qb->getQuery()->getResult();
        } else {
            return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        }
    }

    public function findCompaniesWithSlugName($slugName)
    {

        $result = $this->findEditableCompaniesWithSlugName($slugName, true);
        foreach ($result as $company){
            $this->em->detach($company);
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
