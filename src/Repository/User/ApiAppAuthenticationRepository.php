<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\Decision\Member;
use App\Entity\User\ApiApp;
use App\Entity\User\ApiAppAuthentication;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiAppAuthentication>
 * @phpstan-type ApiAppsArrayType = list<array{
 *      0: ApiApp|null,
 *      firstAuthentication: string,
 *      lastAuthentication: string,
 *  }>
 * @psalm-type ApiAppsArrayType = list<array{
 *      0: ApiApp|null,
 *      firstAuthentication: string,
 *      lastAuthentication: string,
 *  }>
 */
class ApiAppAuthenticationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ApiAppAuthentication::class,
        );
    }

    /**
     * @return ApiAppsArrayType
     *
     * @psalm-suppress LessSpecificReturnStatement, MoreSpecificReturnType Doctrine getResult() is mixed to Psalm.
     */
    public function getFirstAndLastAuthenticationPerApiApp(Member $member): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select(['app', 'MIN(a.time) AS firstAuthentication', 'MAX(a.time) AS lastAuthentication'])
            ->leftJoin(
                ApiApp::class,
                'app',
                'WITH',
                'a.apiApp = app.id',
            )
            ->where('a.user = :user_id')
            ->groupBy('app.appId')
            ->setParameter(
                'user_id',
                $member->getLidnr(),
            );

        return $qb->getQuery()->getResult();
    }

    public function getLastAuthentication(
        User $user,
        ApiApp $app,
    ): ?ApiAppAuthentication {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.apiApp = :app_id')
            ->andWhere('a.user = :user_id')
            ->orderBy(
                'a.time',
                'DESC',
            )
            ->setMaxResults(1)
            ->setParameter(
                'app_id',
                $app->getId(),
            )
            ->setParameter(
                'user_id',
                $user->getLidnr(),
            );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return ApiAppAuthentication[]
     */
    public function getMemberAuthenticationsPerApiApp(Member $member): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->where('a.user = :user_id')
            ->groupBy('a.apiApp')
            ->orderBy(
                'a.time',
                'DESC',
            )
            ->setParameter(
                'user_id',
                $member->getLidnr(),
            );

        return $qb->getQuery()->getResult();
    }
}
