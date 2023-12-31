<?php

declare(strict_types=1);

namespace Frontpage\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Member as MemberModel;
use Frontpage\Model\PollComment as PollCommentModel;

/**
 * Mappers for poll comments.
 *
 * @template-extends BaseMapper<PollCommentModel>
 */
class PollComment extends BaseMapper
{
    /**
     * Get all poll comments made by specific member.
     *
     * @return PollCommentModel[]
     */
    public function findByMember(MemberModel $member): array
    {
        $qb = $this->getRepository()->createQueryBuilder('c');
        $qb->where('c.user = :member')
            ->orderBy('c.createdOn', 'DESC')
            ->setParameter('member', $member);

        return $qb->getQuery()->getResult();
    }

    protected function getRepositoryName(): string
    {
        return PollCommentModel::class;
    }
}
