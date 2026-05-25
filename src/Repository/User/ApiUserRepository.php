<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\ApiUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiUser>
 */
class ApiUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ApiUser::class,
        );
    }

    /**
     * Find an API user by its token.
     *
     * @param string $token Token of the user
     */
    public function findByToken(string $token): ?ApiUser
    {
        return $this->findOneBy(
            [
                'token' => $token,
            ],
        );
    }
}
