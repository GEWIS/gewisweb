<?php

namespace Company\Mapper;

use Company\Model\JobSector;
use Company\Model\JobSector as SectorModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for sectors.
 *
 */
class Sector
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
     * persist a given sector
     *
     * @param $sector JobSector to persist
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function persist($sector)
    {
        $this->em->persist($sector);
        $this->em->flush();
    }

    /**
     * Saves all sectors
     *
     */
    public function save()
    {
        $this->em->flush();
    }

    /**
     * Finds the sector with the given id
     *
     * @param integer $sectorId
     */
    public function findSector($sectorId)
    {
        return $this->getRepository()->findOneBy(['id' => $sectorId]);
    }

    public function findAllSectorsByLanguage($sectorLanguage)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c')
            ->select('c')->where('c.language=:lang')
            ->andWhere('c.hidden=:hidden')
            ->setParameter('lang', $sectorLanguage)
            ->setParameter('hidden', false);

        return $qb->getQuery()->getResult();
    }



    /**
     * Find the same sector, but in the given language
     *
     */
    public function siblingSector($sector, $lang)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c')
            ->select('c')->where('c.languageNeutralId=:sectorId')->andWhere('c.language=:language')
            ->setParameter('sectorId', $sector->getLanguageNeutralId())
            ->setParameter('language', $lang);

        $categories = $qb->getQuery()->getResult();

        return $categories[0];
    }

    /**
     * Find all sectors with the given language neutral Id.
     *
     * @param $sectorId
     * @return mixed
     */
    public function findAllSectorsById($sectorId)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c')
            ->select('c')->where('c.languageNeutralId=:sectorId')
            ->setParameter('sectorId', $sectorId);

        return $qb->getQuery()->getResult();
    }

    /**
     * Deletes the given sector
     *
     * @param SectorModel $sector
     */
    public function delete($sector)
    {
        $this->em->remove($sector);
        $this->em->flush();
    }

    /**
     * Deletes the sector with the given id
     *
     * @param int $sectorId
     */
    public function deleteById($sectorId)
    {
        $sector = $this->findEditableSector($sectorId);
        if (is_null($sector)) {
            return;
        }

        $this->delete($sector);
    }

    /**
     * Find all Categories.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\JobSector');
    }
}
