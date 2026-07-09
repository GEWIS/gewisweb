<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\VacancyCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

use function strtolower;

/**
 * @extends ServiceEntityRepository<VacancyCategory>
 */
class VacancyCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            VacancyCategory::class,
        );
    }

    /**
     * @return VacancyCategory[]
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
     * Searches for a VacancyCategory based on its slug. The value is always converted to lowercase to ensure no weird
     * routing issues occur.
     *
     * @throws NonUniqueResultException
     */
    public function findCategoryBySlug(string $value): ?VacancyCategory
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

    public function findVisibleCategoryById(int $id): ?VacancyCategory
    {
        return $this->findOneBy([
            'id' => $id,
            'hidden' => false,
        ]);
    }
}
