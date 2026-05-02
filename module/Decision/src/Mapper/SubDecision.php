<?php

declare(strict_types=1);

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Member as MemberModel;
use Decision\Model\SubDecision as SubDecisionModel;

use function addcslashes;
use function array_merge;

/**
 * @template-extends BaseMapper<SubDecisionModel>
 */
class SubDecision extends BaseMapper
{
    private const array MEMBER_AWARE_CLASSES = [
        SubDecisionModel\Financial\Budget::class,
        SubDecisionModel\Financial\Statement::class,
        SubDecisionModel\Key\Granting::class,
        SubDecisionModel\OrganRegulation::class,
        SubDecisionModel\Installation::class,
        SubDecisionModel\Board\Installation::class,
        SubDecisionModel\Minutes::class,
    ];

    /**
     * Search sub-decisions.
     *
     * @return SubDecisionModel[]
     */
    public function findByMember(MemberModel $member): array
    {
        $qb = $this->getRepository()->createQueryBuilder('s');
        $qb->where('s.contentNL LIKE :full_name')
            ->setParameter('full_name', '%' . addcslashes($member->getFullName(), '%_') . '%');

        $results = $qb->getQuery()->getResult();

        $em = $this->getEntityManager();
        foreach (self::MEMBER_AWARE_CLASSES as $class) {
            $repo = $em->getRepository($class);

            $results = array_merge(
                $results,
                $repo->findBy(['member' => $member]),
            );
        }

        return $results;
    }

    protected function getRepositoryName(): string
    {
        return SubDecisionModel::class;
    }
}
