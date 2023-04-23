<?php

declare(strict_types=1);

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\JobCategory as JobCategoryModel;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Mappers for category.
 *
 * @template-extends BaseMapper<JobCategoryModel>
 */
class Category extends BaseMapper
{
    /**
     * @return array<array-key, JobCategoryModel>
     */
    public function findVisibleCategories(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('c');
        $qb->where('c.hidden = :hidden')
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
        $qb->innerJoin(
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
     * @param int $id
     *
     * @return JobCategoryModel|null
     */
    public function findVisibleCategoryById(int $id): ?JobCategoryModel
    {
        return $this->getRepository()->findOneBy([
            'id' => $id,
            'hidden' => false,
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return JobCategoryModel::class;
    }
}
