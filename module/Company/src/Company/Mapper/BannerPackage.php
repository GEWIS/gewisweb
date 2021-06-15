<?php

namespace Company\Mapper;

use Company\Model\CompanyBannerPackage as PackageModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for package.
 *
 * NOTE: Packages will be modified externally by a script. Modifycations will be
 * overwritten.
 */
class BannerPackage extends Package
{
    /**
     *
     * Returns an random banner from the active banners
     *
     */
    public function getBannerPackage()
    {
        $banners = $this->findVisiblePackages();
        return empty($banners) ? null : $banners[array_rand($banners)];
    }

    public function addBannerApproval($id){
        $qb = $this->em->createQueryBuilder();
        $qb->add("Company\Model\ApprovalModel\ApprovalPending", "ap");
        $qb->set("ap.type", ":type");
        $qb->set("ap.BannerApproval_id", ":BannerApproval_id");
        $qb->set("ap.rejected", ":rejected");
        $qb->setParameter("type", "banner");
        $qb->setParameter("BannerApproval_id", $id);
        $qb->setParameter("rejected", "0");
        $qb->getQuery()->getResult();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\CompanyBannerPackage');
    }
}
