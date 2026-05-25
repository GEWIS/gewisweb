<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\ApiApp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiApp>
 */
class ApiAppRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ApiApp::class,
        );
    }

    public function findByAppId(string $appId): ?ApiApp
    {
        return $this->findOneBy(
            [
                'appId' => $appId,
            ],
        );
    }
}
