<?php

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\Company as CompanyModel;
use Doctrine\ORM\{
    EntityManager,
    EntityRepository,
    ORMException,
};

/**
 * Mappers for companies.
 *
 * NOTE: Companies will be modified externally by a script. Modifycations will be
 * overwritten.
 */
class Company extends BaseMapper
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
     * Saves all unsaved entities, that are marked persistent.
     *
     * @throws ORMException
     */
    public function save(): void
    {
        $this->em->flush();
    }

    /**
     * @param CompanyModel $entity
     *
     * @throws ORMException
     */
    public function persist(CompanyModel $entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * Find all public companies with a certain locale.
     *
     * @return array
     */
    public function findAllPublic(): array
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.hidden = 0')
            ->orderBy('c.name', 'ASC');

        return array_filter(
            $qb->getQuery()->getResult(),
            function ($company) {
                return $company->getNumberOfPackages() > $company->getNumberOfExpiredPackages();
            }
        );
    }

    /**
     * Find a specific company by its id.
     *
     * @param int $id The id of the company
     *
     * @return CompanyModel|null
     */
    public function findById(int $id): ?CompanyModel
    {
        return $this->getRepository()->find($id);
    }

    /**
     * Find all companies.
     *
     * @return array
     */
    public function findAll(): array
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Return the company with the given slug.
     *
     * @param string $slugName the slugname to find
     *
     * @return CompanyModel|null
     */
    public function findCompanyBySlugName(string $slugName): ?CompanyModel
    {
        $result = $this->getRepository()->findBy(['slugName' => $slugName]);

        return empty($result) ? null : $result[0];
    }

    /**
     * Removes a company.
     *
     * @param CompanyModel $company
     *
     * @throws ORMException
     */
    public function remove(CompanyModel $company)
    {
        $this->em->remove($company);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    protected function getRepositoryName(): string
    {
        return CompanyModel::class;
    }
}
