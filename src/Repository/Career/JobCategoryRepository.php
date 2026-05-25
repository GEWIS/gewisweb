<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\JobCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

use function strtolower;

/**
 * @extends ServiceEntityRepository<JobCategory>
 */
class JobCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            JobCategory::class,
        );
    }

    /**
     * @return JobCategory[]
     */
    public function findVisibleCategories(): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where('c.hidden = :hidden')
            ->setParameter(
                'hidden',
                false,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Searches for a JobCategory based on its slug. The value is always converted to lowercase to ensure no weird
     * routing issues occur.
     *
     * @throws NonUniqueResultException
     */
    public function findCategoryBySlug(string $value): ?JobCategory
    {
        $qb = $this->createQueryBuilder('c');
        $qb->innerJoin(
            'c.slug',
            'loc',
            Join::WITH,
            $qb->expr()->orX(
                'LOWER(loc.valueEN) = :value',
                'LOWER(loc.valueNL) = :value',
            ),
        )
            ->setParameter(
                ':value',
                strtolower($value),
            );

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findVisibleCategoryById(int $id): ?JobCategory
    {
        return $this->findOneBy([
            'id' => $id,
            'hidden' => false,
        ]);
    }
}
