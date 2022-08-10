<?php

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Member as MemberModel;
use User\Model\NewUser as NewUserModel;

class NewUser extends BaseMapper
{
    /**
     * Get the new user by lidnr.
     *
     * @param int $lidnr
     *
     * @return NewUserModel|null
     */
    public function getByLidnr(int $lidnr): ?NewUserModel
    {
        $qb = $this->em->createQueryBuilder();
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
     *
     * @param string $code
     *
     * @return NewUserModel|null
     */
    public function getByCode(string $code): ?NewUserModel
    {
        $qb = $this->em->createQueryBuilder();
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
     *
     * @param MemberModel $member
     *
     * @psalm-return list<mixed>
     */
    public function deleteByMember(MemberModel $member): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->delete($this->getRepositoryName(), 'u');
        $qb->where('u.member = :member');
        $qb->setParameter('member', $member);

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return NewUserModel::class;
    }
}
