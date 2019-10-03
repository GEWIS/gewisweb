<?php

namespace Frontpage\Mapper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;

/**
 * Mappers for NewsItems.
 *
 */
class NewsItem
{

    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Returns a news item based on its id.
     *
     * @param integer $newsItemId
     * @return \Frontpage\Model\NewsItem|null
     */
    public function findNewsItemById($newsItemId)
    {
        return $this->getRepository()->find($newsItemId);
    }

    /**
     * Retrieves a certain number of news items sorted descending by their date.
     *
     * @param integer $count
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


    /**
     * Removes a news item.
     *
     * @param \Frontpage\Model\NewsItem $newsItem
     */
    public function remove($newsItem)
    {
        $this->em->remove($newsItem);
    }

    /**
     * Persist a news item.
     *
     * @param \Frontpage\Model\NewsItem $newsItem
     */
    public function persist($newsItem)
    {
        $this->em->persist($newsItem);
    }

    /**
     * Flush.
     */
    public function flush()
    {
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Frontpage\Model\NewsItem');
    }
}
