<?php

declare(strict_types=1);

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Member as MemberModel;
use User\Model\NewUser as NewUserModel;

/**
 * @template-extends BaseMapper<NewUserModel>
 */
class NewUser extends BaseMapper
{
    /**
     * Get the new user by lidnr.
     */
    public function getByLidnr(int $lidnr): ?NewUserModel
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u, m')
            ->from($this->getRepositoryName(), 'u')
            ->join('u.member', 'm')
            ->where('u.lidnr = ?1');
        $qb->setParameter(1, $lidnr);
        $qb->setMaxResults(1);

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Get the new user by code.
     */
    public function getByCode(string $code): ?NewUserModel
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u, m')
            ->from($this->getRepositoryName(), 'u')
            ->join('u.member', 'm')
            ->where('u.code = ?1');
        $qb->setParameter(1, $code);
        $qb->setMaxResults(1);

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Delete the existing activation code for a member.
     */
    public function deleteByMember(MemberModel $member): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getRepositoryName(), 'u')
            ->where('u.member = :member')
            ->setParameter('member', $member);
        $qb->getQuery()->execute();
    }

    protected function getRepositoryName(): string
    {
        return NewUserModel::class;
    }
}
