<?php

namespace Frontpage\Mapper;

use Application\Mapper\BaseMapper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Frontpage\Model\NewsItem as NewsItemModel;

/**
 * Mappers for NewsItems.
 */
class NewsItem extends BaseMapper
{
    /**
     * Retrieves a certain number of news items sorted descending by their date.
     *
     * @param int $count
     *
     * @return array
     */
    public function getLatestNewsItems($count)
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
    public function getPaginatorAdapter()
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
