<?php

declare(strict_types=1);

namespace App\Repository\Frontpage;

use App\Entity\Application\Enums\Languages;
use App\Entity\Frontpage\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function mb_strtoupper;

/**
 * @extends ServiceEntityRepository<Page>
 */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Page::class,
        );
    }

    /**
     * Find a single Page by its localised slug path.
     */
    public function findPage(
        Languages $language,
        string $category,
        ?string $subCategory = null,
        ?string $name = null,
    ): ?Page {
        $queryLanguage = mb_strtoupper($language->getLangParam());

        $qb = $this->createQueryBuilder('p')
            ->innerJoin(
                'p.category',
                'c',
            ) // no WITH
            ->innerJoin(
                'p.subCategory',
                's',
            ) // no WITH
            ->innerJoin(
                'p.name',
                'n',
            ); // no WITH

        // Add conditions in WHERE instead (EAGER fetch is incompatible with an innerJoin using WITH for conditionals).
        $qb->where('c.value' . $queryLanguage . ' = :category')
            ->setParameter(
                'category',
                $category,
            );

        if (null !== $subCategory) {
            $qb->andWhere('s.value' . $queryLanguage . ' = :subCategory')
                ->setParameter(
                    'subCategory',
                    $subCategory,
                );
        } else {
            $qb->andWhere('s.value' . $queryLanguage . ' IS NULL');
        }

        if (null !== $name) {
            $qb->andWhere('n.value' . $queryLanguage . ' = :name')
                ->setParameter(
                    'name',
                    $name,
                );
        } else {
            $qb->andWhere('n.value' . $queryLanguage . ' IS NULL');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
