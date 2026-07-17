<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\ExternalApp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExternalApp>
 */
class ExternalAppRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ExternalApp::class,
        );
    }

    public function findByAppId(string $appId): ?ExternalApp
    {
        return $this->findOneBy(
            [
                'appId' => $appId,
            ],
        );
    }
}
