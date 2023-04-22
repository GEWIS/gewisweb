<?php

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use DateTime;
use Decision\Model\Member as MemberModel;
use User\Model\{
    ApiApp as ApiAppModel,
    User as UserModel,
};
use User\Model\ApiAppAuthentication as ApiAppAuthenticationModel;

/**
 * @template-extends BaseMapper<ApiAppAuthenticationModel>
 */
class ApiAppAuthentication extends BaseMapper
{
    /**
     * @return array<array-key, array{0: ApiAppModel, firstAuthentication: DateTime, lastAuthentication: DateTime}>
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
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return ApiAppAuthenticationModel::class;
    }
}
