<?php

namespace Company\Mapper;

use Doctrine\ORM\EntityManager;

/**
 * Mappers for package.
 *
 * NOTE: Packages will be modified externally by a script. Modifycations will be
 * overwritten.
 */
class HighlightPackage extends Package
{

    /**
     * Find all categories in which a company has highlighted a vacancy
     *
     * @param integer $companyId the id of the company who's
     * highlighted categories will be fetched.
     *
     * @return array Company\Model\JobCategory.
     */
    public function findHighlightedCategories($companyId)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement

        $qb = $objectRepository->createQueryBuilder('h');
        $qb->select('jc.languageNeutralId')
            ->distinct()
            ->join('h.vacancy', 'j')
            ->join('j.category', 'jc')
            ->where('h.company = ?1')
            ->setParameter(1, $companyId);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find the highlights a company has
     *
     * @param integer $companyId the id of the company who's
     * number of highlights will be fetched.
     *
     * @return array with the vacancyId of the highlighted vacancy and the expiration date of the highlight
     */
    public function findCurrentHighlights($companyId)
    {
        $today = date("Y/m/d");

        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('h');
        $qb->select('IDENTITY(h.vacancy), h.expires')
            ->where('h.company = ?1')
            ->andWhere('h.expires >= ?2')
            ->andWhere('h.published = 1')
            ->setParameter(1, $companyId)
            ->setParameter(2, $today);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find the number of highlights a company has
     *
     * @param integer $companyId the id of the company who's
     * number of highlights will be fetched.
     *
     * @return int number of highlights
     */
    public function findNumberOfHighlightsPerCompany($companyId)
    {
        $today = date("Y/m/d");

        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('h');
        $qb->select('COUNT(h)')
            ->where('h.company = ?1')
            ->andWhere('h.expires >= ?2')
            ->andWhere('h.published = 1')
            ->setParameter(1, $companyId)
            ->setParameter(2, $today);

        return $qb->getQuery()->getResult()[0][1];
    }

    public function findAllActiveHighlights()
    {
        $today = date("Y/m/d");

        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('h');
        $qb->select('h')
            ->where('h.starts <= ?1')
            ->andWhere('h.expires >= ?1')
            ->andWhere('h.published = 1')
            ->setParameter(1, $today);

        return $qb->getQuery()->getResult();
    }

    public function findAllActiveHighlightsList()
    {
        $today = date("Y/m/d");

        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('h');
        $qb->select('IDENTITY(h.vacancy)', 'c.name', 'h.starts', 'h.expires', 'jc.languageNeutralId', 'h.id', 'c.slugName')
            ->join('h.company', 'c')
            ->join('h.vacancy', 'j')
            ->join('j.category', 'jc')
            ->where('h.starts <= ?1')
            ->andWhere('h.expires >= ?1')
            ->andWhere('h.published = 1')
            ->setParameter(1, $today);

        return $qb->getQuery()->getResult();
    }

    public function findVacancies($company)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('h');
        $qb->select('IDENTITY(h.vacancy)', 'j.name')
            ->join('h.vacancy', 'j')
            ->Where('h.company = ?2' )
            ->andWhere('h.published = 1')
            ->setParameter(2, $company);

        return $qb->getQuery()->getResult();
    }

    /**
     * Pick vacancies that are visible as highlighted
     *
     * @return array id's of highlighted vacancies
     */
    public function getHighlightedVacancies($category, $language)
    {
        $today = date("Y/m/d");
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('h');
        $qb->select('IDENTITY(h.vacancy)')
            ->join('h.vacancy', 'j')
            ->join('j.category', 'jc')
            ->where('h.expires >= ?1')
            ->setParameter(1, $today)
            ->andWhere('j.language = ?2')
            ->setParameter(2, $language)
            ->andWhere('h.published = 1');
        if ($category!=NULL) {
            $qb->andWhere('j.category = ?5')
                ->setParameter(5, $category);
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * Find the number of highlights in a category
     *
     * @param integer $categoryId the id of the category who's
     * number of highlights will be fetched.
     *
     * @return int number of highlights
     */
    public function findNumberOfHighlightsPerCategory($categoryId)
    {
        $today = date("Y/m/d");

        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('h');
        $qb->select('COUNT(h)')
            ->join('h.vacancy', 'j')
            ->where('h.expires >= ?1')
            ->andWhere('h.published = 1')
            ->andWhere('j.category = ?2')
            ->setParameter(1, $today)
            ->setParameter(2, $categoryId);

        return $qb->getQuery()->getResult()[0][1];
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\CompanyHighlightPackage');
    }
}
