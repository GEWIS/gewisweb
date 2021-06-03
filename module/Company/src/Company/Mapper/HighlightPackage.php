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
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\CompanyHighlightPackage');
    }
}
