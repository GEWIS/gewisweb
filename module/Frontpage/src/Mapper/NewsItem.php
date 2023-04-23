<?php

declare(strict_types=1);

namespace Frontpage\Mapper;

use Application\Mapper\BaseMapper;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Frontpage\Model\NewsItem as NewsItemModel;

/**
 * Mappers for NewsItems.
 *
 * @template-extends BaseMapper<NewsItemModel>
 */
class NewsItem extends BaseMapper
{
    /**
     * Retrieves a certain number of news items sorted descending by their date.
     *
     * @param int $count
     *
     * @return array<array-key, NewsItemModel>
     */
    public function getLatestNewsItems(int $count): array
    {
        $qb = $this->getRepository()->createQueryBuilder('newsItem');
        $qb->addOrderBy('newsItem.pinned', 'DESC')
            ->addOrderBy('newsItem.date', 'DESC')
            ->setMaxResults($count);

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns a paginator adapter for paging through all news items.
     *
     * @return DoctrineAdapter
     */
    public function getPaginatorAdapter(): DoctrineAdapter
    {
        $qb = $this->getRepository()->createQueryBuilder('newsItem');
        $qb->orderBy('newsItem.date', 'DESC');

        return new DoctrineAdapter(new ORMPaginator($qb));
    }

    protected function getRepositoryName(): string
    {
        return NewsItemModel::class;
    }
}
