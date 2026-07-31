<?php

declare(strict_types=1);

namespace App\Repository\Application;

use App\Entity\Application\Announcement;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template-extends ServiceEntityRepository<Announcement>
 */
class AnnouncementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Announcement::class,
        );
    }

    /**
     * @return Announcement[]
     */
    public function findAllNewestFirst(): array
    {
        return $this->findBy(
            [],
            ['createdAt' => 'DESC'],
        );
    }

    /**
     * @return Announcement[]
     */
    public function findActive(DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.endsAt > :now')
            ->setParameter(
                'now',
                $now,
                Types::DATETIME_IMMUTABLE,
            )
            ->orderBy(
                'a.createdAt',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }
}
