<?php

declare(strict_types=1);

namespace App\Repository\Application;

use App\Entity\Application\MaintenanceWindow;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

use function array_filter;
use function array_values;

/**
 * @template-extends ServiceEntityRepository<MaintenanceWindow>
 */
class MaintenanceWindowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            MaintenanceWindow::class,
        );
    }

    /**
     * @return MaintenanceWindow[]
     */
    public function findAllOrdered(): array
    {
        return $this->findBy(
            [],
            ['startsAt' => 'ASC'],
        );
    }

    public function findActiveAt(DateTimeImmutable $now): ?MaintenanceWindow
    {
        return $this->createQueryBuilder('w')
            ->where('(w.startsAt IS NULL OR w.startsAt <= :now)')
            ->andWhere('(w.endsAt IS NULL OR w.endsAt > :now)')
            ->setParameter(
                'now',
                $now,
                Types::DATETIME_IMMUTABLE,
            )
            ->orderBy(
                'w.startsAt',
                'ASC',
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Every other window whose interval clashes with the given one, so the form can refuse an overlapping schedule.
     *
     * @return MaintenanceWindow[]
     */
    public function findOverlapping(MaintenanceWindow $window): array
    {
        $id = $window->getId();
        if (null === $id) {
            $others = $this->findAll();
        } else {
            $others = $this->createQueryBuilder('w')
                ->where('w.id != :id')
                ->setParameter(
                    'id',
                    $id,
                )
                ->getQuery()
                ->getResult();
        }

        return array_values(array_filter(
            $others,
            static fn (MaintenanceWindow $other): bool => $window->overlaps($other),
        ));
    }
}
