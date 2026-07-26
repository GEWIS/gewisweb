<?php

declare(strict_types=1);

namespace App\Repository\Application;

use App\Entity\Application\Notification;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template-extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Notification::class,
        );
    }

    /**
     * The most recent notifications within the window, newest first. When $cacheTtl is positive the result is cached
     * for that many seconds; the same list serves every member, so a caller passing a coarse (e.g. hour-aligned)
     * $since gets a shared cache entry.
     *
     * @return Notification[]
     */
    public function findRecent(
        DateTimeImmutable $since,
        int $limit,
        int $cacheTtl = 0,
    ): array {
        $query = $this->createQueryBuilder('n')
            ->where('n.createdAt > :since')
            ->setParameter(
                'since',
                $since,
                Types::DATETIME_IMMUTABLE,
            )
            ->orderBy(
                'n.createdAt',
                'DESC',
            )
            ->setMaxResults($limit)
            ->getQuery();

        if ($cacheTtl > 0) {
            $query->enableResultCache($cacheTtl);
        }

        return $query->getResult();
    }
}
