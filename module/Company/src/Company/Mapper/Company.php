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
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Saves all unsaved entities, that are marked persistent
     *
     */
    public function save()
    {
        $this->em->flush();
    }

    /**
     *
     * Checks if $slugName is only used by object identified with $cid
     *
     * @param string $slugName The slugName to be checked
     * @param int $cid The id to ignore
     *
     */
    public function isSlugNameUnique($slugName, $cid)
    {
        $objects = $this->findEditableCompaniesBySlugName($slugName, true);
        foreach ($objects as $company) {
            if ($company->getId() != $cid) {
                return false;
            }
        }
        return true;
    }

    /**
     * Inserts a company into the datebase, and initializes the given 
     * translations as empty translations for them
     *
     * @param mixed $languages
     */
    public function insert($languages)
    {
        $company = new CompanyModel($this->em);

        $companiesBySameSlugName = $this->findEditableCompaniesBySlugName($company->getSlugName(), false);

        // Only for testing, logo will be implemented in a later issue, and it will be validated before it comes here, so this will never be called in production code. TODO: remove this when implemented logo and logo validation


        foreach ($languages as $language) {
            $translation = new CompanyI18n($language, $company);
            if (is_null($translation->getLogo())) {
                $translation->setLogo('');
            }
            $this->em->persist($translation);
            $company->addTranslation($translation);
        }

        $company->setHidden(false);
        $this->em->persist($company);

        return $company;
    }

    /**
     * Find all public companies with a certain locale
     *
     * @return array
     */
    public function findPublicByLocale($locale)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')
            ->join('c.translations', 't')
            ->where('c.hidden=0')
            ->andWhere('t.language = ?1')
            ->setParameter(1, $locale)
            ->orderBy('c.name', 'ASC');
        return array_filter($qb->getQuery()->getResult(), function ($company) {
            return $company->getNumberOfPackages() > $company->getNumberOfExpiredPackages();
        });
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
     * Find the company with the given slugName.
     *
     * @param slugName The 'username' of the company to get.
     * @param asObject if yes, returns the company as an object in an array, otherwise returns the company as an array of an array
     *
     * @return An array of companies with the given slugName.
     */
    public function findEditableCompaniesBySlugName($slugName, $asObject)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.slugName=:slugCompanyName');
        $qb->setParameter('slugCompanyName', $slugName);
        $qb->setMaxResults(1);
        if ($asObject) {
            return $qb->getQuery()->getResult();
        }
        return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    /**
     * Return the company with the given slug
     *
     * @param string $slugName the slugname to find
     *
     * @return \Company\Model\Company | null
     */
    public function findCompanyBySlugName($slugName)
    {
        $result = $this->getRepository()->findBy(['slugName' => $slugName]);
        return empty($result) ? null : $result[0];
    }


    /**
     * Removes a company.
     *
     * @param $company
     */
    public function remove($company)
    {
        $this->em->remove($company);
        $this->em->flush();
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
