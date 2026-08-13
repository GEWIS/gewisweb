<?php

declare(strict_types=1);

namespace App\Repository\Frontpage;

use App\Entity\Decision\AssociationYear;
use App\Entity\Frontpage\Enums\NewsCategory;
use App\Entity\Frontpage\NewsItem;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

use function addcslashes;
use function array_keys;
use function mb_strtolower;
use function rsort;
use function sprintf;
use function trim;

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
     * What the board pinned first, then everything else newest first.
     *
     * @return NewsItem[]
     */
    public function findFeed(int $limit = 6): array
    {
        return $this->feedQuery()
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Paginator<NewsItem>
     */
    public function getPaginatorAdapter(
        int $page = 1,
        int $limit = 15,
    ): Paginator {
        $paginator = new Paginator($this->feedQuery());
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        return $paginator;
    }

    /**
     * The archive, newest first, narrowed by the reader's filters. Nothing is pinned to the top here: the archive is
     * read by date, and an item held above the months it belongs to would sit under the wrong one.
     *
     * @return Paginator<NewsItem>
     */
    public function findForOverview(
        ?int $year,
        ?NewsCategory $category,
        string $search,
        string $locale,
        int $limit,
    ): Paginator {
        $builder = $this->createQueryBuilder('newsItem')
            ->orderBy(
                'newsItem.date',
                'DESC',
            )
            ->setMaxResults($limit);

        if (null !== $category) {
            $builder->andWhere('newsItem.category = :category')
                ->setParameter(
                    'category',
                    $category->value,
                );
        }

        if (null !== $year) {
            $associationYear = AssociationYear::fromYear($year);
            $builder->andWhere('newsItem.date BETWEEN :yearStart AND :yearEnd')
                ->setParameter(
                    'yearStart',
                    $associationYear->getStartDate(),
                    Types::DATE_MUTABLE,
                )
                ->setParameter(
                    'yearEnd',
                    $associationYear->getEndDate(),
                    Types::DATE_MUTABLE,
                );
        }

        $search = trim($search);
        if ('' !== $search) {
            $title = 'nl' === $locale
                ? 'newsItem.dutchTitle'
                : 'newsItem.englishTitle';
            $content = 'nl' === $locale
                ? 'newsItem.dutchContent'
                : 'newsItem.englishContent';
            // The wildcards in what was typed are escaped so they are matched as the characters they are.
            $builder->andWhere(
                'LOWER(' . $title . ') LIKE :needle OR LOWER(' . $content . ') LIKE :needle',
            )
                ->setParameter(
                    'needle',
                    '%' . addcslashes(
                        mb_strtolower($search),
                        '%_',
                    ) . '%',
                );
        }

        return new Paginator($builder);
    }

    /**
     * The association years that have news in them, newest first, for the year switcher.
     *
     * @return int[]
     */
    public function findYears(): array
    {
        // Aggregated to distinct year-month pairs, so the switcher costs a handful of rows however much news there is.
        /** @var list<array{y: int|string, m: int|string}> $rows */
        $rows = $this->createQueryBuilder('newsItem')
            ->select(
                'YEAR(newsItem.date) AS y',
                'MONTH(newsItem.date) AS m',
            )
            ->distinct()
            ->getQuery()
            ->getResult();

        $years = [];
        foreach ($rows as $row) {
            $month = new DateTime(sprintf(
                '%d-%d-15',
                $row['y'],
                $row['m'],
            ));
            $years[AssociationYear::fromDate($month)->getYear()] = true;
        }

        $years = array_keys($years);
        rsort($years);

        return $years;
    }

    private function feedQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('newsItem')
            ->addOrderBy(
                'newsItem.pinned',
                'DESC',
            )
            ->addOrderBy(
                'newsItem.date',
                'DESC',
            );
    }
}
