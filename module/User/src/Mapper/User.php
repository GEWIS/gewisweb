<?php

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use User\Model\User as UserModel;

class User extends BaseMapper
{
    /**
     * Find a user by its login.
     */
    public function findByLogin(string $login): ?UserModel
    {
        // create query for user
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u, r, m')
            ->from($this->getRepositoryName(), 'u')
            ->leftJoin('u.roles', 'r')
            ->join('u.member', 'm');

        // depending on login, add correct where clause
        if (is_numeric($login)) {
            $qb->where('u.lidnr = ?1');
        } else {
            $qb->where('LOWER(m.email) = ?1');
        }

        // set the parameters
        $qb->setParameter(1, strtolower($login));
        $qb->setMaxResults(1);

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return UserModel::class;
    }
}
