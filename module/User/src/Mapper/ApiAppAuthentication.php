<?php

declare(strict_types=1);

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use DateTime;
use Decision\Model\Member as MemberModel;
use User\Model\ApiApp as ApiAppModel;
use User\Model\ApiAppAuthentication as ApiAppAuthenticationModel;
use User\Model\User as UserModel;

/**
 * @template-extends BaseMapper<ApiAppAuthenticationModel>
 * @psalm-type ApiAppsArrayType = array<array-key, array{
 *     0: ApiAppModel,
 *     firstAuthentication: DateTime,
 *     lastAuthentication: DateTime,
 * }>
 */
class ApiAppAuthentication extends BaseMapper
{
    /**
     * @return ApiAppsArrayType
     */
    public function getFirstAndLastAuthenticationPerApiApp(MemberModel $member): array
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->select(['app', 'MIN(a.time) AS firstAuthentication', 'MAX(a.time) AS lastAuthentication'])
            ->leftJoin(ApiAppModel::class, 'app', 'WITH', 'a.apiApp = app.id')
            ->where('a.user = :user_id')
            ->groupBy('app.appId')
            ->setParameter('user_id', $member->getLidnr());

        return $qb->getQuery()->getResult();
    }

    public function getLastAuthentication(
        UserModel $user,
        ApiAppModel $app,
    ): ?ApiAppAuthenticationModel {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.apiApp = :app_id')
            ->andWhere('a.user = :user_id')
            ->orderBy('a.time', 'DESC')
            ->setMaxResults(1)
            ->setParameter('app_id', $app->getId())
            ->setParameter('user_id', $user->getLidnr());

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return ApiAppAuthenticationModel[]
     */
    public function getMemberAuthenticationsPerApiApp(MemberModel $member): array
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->select('a')
            ->where('a.user = :user_id')
            ->groupBy('a.apiApp')
            ->orderBy('a.time', 'DESC')
            ->setParameter('user_id', $member->getLidnr());

        return $qb->getQuery()->getResult();
    }

    protected function getRepositoryName(): string
    {
        return ApiAppAuthenticationModel::class;
    }
}
