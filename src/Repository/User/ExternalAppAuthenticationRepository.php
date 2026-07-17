<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\Decision\Member;
use App\Entity\User\ExternalApp;
use App\Entity\User\ExternalAppAuthentication;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExternalAppAuthentication>
 * @phpstan-type ExternalAppsArrayType = list<array{
 *      0: ExternalApp|null,
 *      firstAuthentication: string,
 *      lastAuthentication: string,
 *  }>
 * @psalm-type ExternalAppsArrayType = list<array{
 *      0: ExternalApp|null,
 *      firstAuthentication: string,
 *      lastAuthentication: string,
 *  }>
 */
class ExternalAppAuthenticationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ExternalAppAuthentication::class,
        );
    }

    /**
     * @return ExternalAppsArrayType
     *
     * @psalm-suppress LessSpecificReturnStatement, MoreSpecificReturnType Doctrine getResult() is mixed to Psalm.
     */
    public function getFirstAndLastAuthenticationPerExternalApp(Member $member): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select(['app', 'MIN(a.time) AS firstAuthentication', 'MAX(a.time) AS lastAuthentication'])
            ->leftJoin(
                ExternalApp::class,
                'app',
                'WITH',
                'a.externalApp = app.id',
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
        ExternalApp $app,
    ): ?ExternalAppAuthentication {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.externalApp = :app_id')
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
     * @return ExternalAppAuthentication[]
     */
    public function getMemberAuthenticationsPerExternalApp(Member $member): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->where('a.user = :user_id')
            ->groupBy('a.externalApp')
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
