<?php

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Decision as DecisionModel;

class Decision extends BaseMapper
{
    /**
     * Search decisions.
     *
     * @param string $query
     *
     * @return array
     */
    public function search($query)
    {
        $qb = $this->getRepository()->createQueryBuilder('d');

        $qb->select('d, m')
            ->where('d.content LIKE :query')
            ->join('d.meeting', 'm')
            ->orderBy('m.date', 'DESC')
            ->setMaxResults(50);

        $qb->setParameter('query', "%$query%");

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return DecisionModel::class;
    }
}
