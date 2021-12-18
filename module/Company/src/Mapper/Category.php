<?php

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\JobCategory as JobCategoryModel;
use Doctrine\ORM\{
    NonUniqueResultException
};
use Doctrine\ORM\Query\Expr\Join;

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
     * Searches for a JobCategory based on its slug. The value is always converted to lowercase to ensure no weird
     * routing issues occur.
     *
     * @param string $value
     *
     * @return JobCategoryModel|null
     * @throws NonUniqueResultException
     */
    public function findCategoryBySlug(string $value): ?JobCategoryModel
    {
        $qb = $this->getRepository()->createQueryBuilder('c');
        $qb->select('c')
            ->innerJoin(
                'c.slug',
                'loc',
                Join::WITH,
                $qb->expr()->orX(
                    'LOWER(loc.valueEN) = :value',
                    'LOWER(loc.valueNL) = :value',
                )
            )
            ->setParameter(':value', strtolower($value));

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
