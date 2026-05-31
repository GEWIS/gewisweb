<?php

declare(strict_types=1);

namespace App\Repository\Application;

use App\Entity\Application\EditLock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template-extends ServiceEntityRepository<EditLock>
 */
class EditLockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            EditLock::class,
        );
    }

    public function findOneByResource(
        string $resourceId,
        int $resourceKey,
    ): ?EditLock {
        return $this->findOneBy([
            'resourceId' => $resourceId,
            'resourceKey' => $resourceKey,
        ]);
    }
}
