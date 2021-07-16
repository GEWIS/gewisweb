<?php

namespace Company\Mapper;

use Company\Model\CompanyBannerPackage as BannerPackageModel;
use Company\Model\CompanyFeaturedPackage as FeaturedPackageModel;
use Company\Model\CompanyJobPackage as PackageModel;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

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
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Saves all packages.
     */
    public function save()
    {
        $this->em->flush();
    }

    /**
     * Finds the package with the given id.
     *
     * @param int $packageId
     */
    public function findPackage($packageId)
    {
        return $this->getRepository()->findOneBy(['id' => $packageId]);
    }

    /**
     * Deletes the given package.
     */
    public function delete($packageId)
    {
        $package = $this->findEditablePackage($packageId);
        if (is_null($package)) {
            return;
        }

        $this->em->remove($package);
        $this->em->flush();
    }

    /**
     * Will return a list of published packages that will expire between now and $date.
     *
     * @param DateTime $date The date until where to search
     */
    public function findFuturePackageExpirationsBeforeDate($date)
    {
        $objectRepository = $this->getRepository();
        $qb = $objectRepository->createQueryBuilder('p');
        $qb->select('p')
            ->where('p.published=1')
            // All packages that will expire between today and then, ordered smallest first
            ->andWhere('p.expires>CURRENT_DATE()')
            ->andWhere('p.expires<=:date')
            ->orderBy('p.expires', 'ASC')
            ->setParameter('date', $date);

        return $qb->getQuery()->getResult();
    }

    /**
     * Will return a list of published packages that will expire between now and $date.
     *
     * @param DateTime $date The date until where to search
     */
    public function findFuturePackageStartsBeforeDate($date)
    {
        $objectRepository = $this->getRepository();
        $qb = $objectRepository->createQueryBuilder('p');
        $qb->select('p')
            ->where('p.published=1')
            // All packages that will start between today and then, ordered smallest first
            ->andWhere('p.starts>CURRENT_DATE()')
            ->andWhere('p.starts<=:date')
            ->orderBy('p.starts', 'ASC')
            ->setParameter('date', $date);

        return $qb->getQuery()->getResult();
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

    protected function getVisiblePackagesQueryBuilder()
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('p');
        $qb->select('p')
            ->where('p.published=1')
            ->andWhere('p.starts<=CURRENT_DATE()')
            ->andWhere('p.expires>=CURRENT_DATE()');

        return $qb;
    }

    /**
     * Find all packages that should be visible, and returns an editable version of them.
     *
     * @return array
     */
    public function findVisiblePackages()
    {
        $qb = $this->getVisiblePackagesQueryBuilder();

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all packages, and returns an editable version of them.
     *
     * @return PackageModel|null
     */
    public function findEditablePackage($packageId)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('p');
        $qb->select('p')->where('p.id=:packageId')
            ->setParameter('packageId', $packageId)
            ->setMaxResults(1);

        $packages = $qb->getQuery()->getResult();

        if (1 != count($packages)) {
            return null;
        }

        return $packages[0];
    }

    private function createPackage($type)
    {
        if ('job' === $type) {
            return new PackageModel();
        }

        if ('featured' === $type) {
            return new FeaturedPackageModel();
        }

        return new BannerPackageModel();
    }

    /**
     * Inserts a new package into the given company.
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
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\CompanyJobPackage');
    }
}
