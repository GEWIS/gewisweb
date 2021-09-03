<?php

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\JobCategory as JobCategoryModel;
use Doctrine\ORM\NonUniqueResultException;

/**
 * Mappers for category.
 */
class Category extends BaseMapper
{
    /**
     * Finds the category with the given id.
     *
     * @param int $categorySlug
     */
    public function findCategory($categorySlug)
    {
        return $this->getRepository()->findOneBy(['slug' => $categorySlug]);
    }

    /**
     * @return array
     */
    public function findVisibleCategories(): array
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c')
            ->select('c')
            ->where('c.hidden = :hidden')
            ->setParameter('hidden', false);

        return $qb->getQuery()->getResult();
    }

    /**
     *
     * @param string $value
     *
     * @return JobCategoryModel|null
     * @throws NonUniqueResultException
     */
    public function findCategoryBySlug(string $value): ?JobCategoryModel
    {
        $qb = $this->getRepository()->createQueryBuilder('c')
            ->select('c')
            ->innerJoin('c.pluralName', 'l', 'WITH', 'l.valueEN = :value')
            ->setParameter(':value', $value);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return JobCategoryModel::class;
    }
}
