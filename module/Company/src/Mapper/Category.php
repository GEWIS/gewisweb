<?php

declare(strict_types=1);

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\JobCategory as JobCategoryModel;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Override;

use function strtolower;

/**
 * Mappers for category.
 *
 * @template-extends BaseMapper<JobCategoryModel>
 */
class Category extends BaseMapper
{
    /**
     * @return JobCategoryModel[]
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
            ),
        )
            ->setParameter(':value', strtolower($value));

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findVisibleCategoryById(int $id): ?JobCategoryModel
    {
        return $this->getRepository()->findOneBy([
            'id' => $id,
            'hidden' => false,
        ]);
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return JobCategoryModel::class;
    }
}
