<?php

namespace Company\Mapper;

use Company\Model\CompanyJobPackage as PackageModel;
use Company\Model\CompanyBannerPackage as BannerPackageModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for package.
 *
 * NOTE: Packages will be modified externally by a script. Modifycations will be
 * overwritten.
 */
class Package
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
     * Saves all packages
     *
     */
    public function save()
    {
        $this->em->flush();
    }

    /**
     * Deletes the given package
     *
     */
    public function delete($packageID)
    {
        $package = $this->findEditablePackage($packageID);
        if (is_null($package)) {
            return;
        }

        $this->em->remove($package);
        $this->em->flush();
    }

    /**
     * Find all Packages.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Find all packages that should be visible, and returns an editable version of them.
     *
     * @return array
     */
    public function findVisiblePackages()
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('p');
        $qb->select('p')
            ->where('p.published=1')
            ->andWhere('p.starts<=CURRENT_DATE()')
            ->andWhere('p.expires>=CURRENT_DATE()');
        $packages = $qb->getQuery()->getResult();

        return $packages;

    }

    /**
     * Find all packages, and returns an editable version of them.
     *
     * @return array
     */
    public function findEditablePackage($packageID)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('p');
        $qb->select('p')->where('p.id=:packageID');
        $qb->setParameter('packageID', $packageID);
        $qb->setMaxResults(1);
        $packages = $qb->getQuery()->getResult();
        if (count($packages) != 1) {
            return;
        }

        return $packages[0];
    }

    private function createPackage($type)
    {
        if ($type === "job") {
            return new PackageModel($this->em);
        }
        return new BannerPackageModel($this->em);
    }
    /**
     * Inserts a new package into the given company
     *
     */
    public function insertPackageIntoCompany($company, $type)
    {
        $package = $this->createPackage($type);
        $package->setCompany($company);
        $this->em->persist($package);

        return $package;
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\CompanyJobPackage');
    }
}
