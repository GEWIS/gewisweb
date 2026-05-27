<?php

declare(strict_types=1);

namespace App\Repository\Frontpage;

use App\Entity\Frontpage\NewsItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsItem>
 */
class NewsItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            NewsItem::class,
        );
    }

    /**
     * Retrieves a certain number of news items sorted descending by their date.
     *
     * @return NewsItem[]
     */
    public function getLatestNewsItems(int $count): array
    {
        $qb = $this->createQueryBuilder('newsItem');
        $qb->addOrderBy(
            'newsItem.pinned',
            'DESC',
        )
            ->addOrderBy(
                'newsItem.date',
                'DESC',
            )
            ->setMaxResults($count);

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns a paginator adapter for paging through all news items.
     *
     * @return Paginator<NewsItem>
     */
    public function getPaginatorAdapter(
        int $page = 1,
        int $limit = 15,
    ): Paginator {
        $qb = $this->createQueryBuilder('newsItem');
        $qb->orderBy(
            'newsItem.date',
            'DESC',
        );

        $paginator = new Paginator($qb);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        return $paginator;
    }
}
