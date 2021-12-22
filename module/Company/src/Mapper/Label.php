<?php

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\JobLabel as JobLabelModel;

/**
 * Mappers for labels.
 */
class Label extends BaseMapper
{
    /**
     * @return array
     */
    public function findVisibleLabels(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('l');
        $qb->where('l.hidden = :hidden')
            ->setParameter('hidden', false);

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return JobLabeLModel::class;
    }
}
