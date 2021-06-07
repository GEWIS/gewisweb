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
     * Find the number of highlights a company has
     *
     * @param integer $companyId the id of the company who's
     * number of highlights will be fetched.
     *
     * @return int number of highlights
     */
    public function getNumberOfHighlights($companyId)
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
