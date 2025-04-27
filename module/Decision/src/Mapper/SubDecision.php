<?php

declare(strict_types=1);

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Member as MemberModel;
use Decision\Model\SubDecision as SubDecisionModel;

use function addcslashes;

/**
 * @template-extends BaseMapper<SubDecisionModel>
 */
class SubDecision extends BaseMapper
{
    /**
     * Search sub-decisions.
     *
     * @return SubDecisionModel[]
     */
    public function findByMember(MemberModel $member): array
    {
        $qb = $this->getRepository()->createQueryBuilder('s');
        $qb->select('s')
            ->where('s.contentNL LIKE :full_name')
            ->orWhere('s.member = :member');

        $qb->setParameter('full_name', '%' . addcslashes($member->getFullName(), '%_') . '%')
            ->setParameter('member', $member);

        return $qb->getQuery()->getResult();
    }

    protected function getRepositoryName(): string
    {
        return SubDecisionModel::class;
    }
}
