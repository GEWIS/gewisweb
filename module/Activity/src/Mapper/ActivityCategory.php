<?php

namespace Activity\Mapper;

use Activity\Model\ActivityCategory as ActivityCategoryModel;
use Application\Mapper\BaseMapper;
use Doctrine\ORM\EntityManager;

class ActivityCategory extends BaseMapper
{
    /**
     * Get a Category by an Id.
     *
     * @param int $id
     *
     * @return ActivityCategoryModel
     */
    public function getCategoryById($id)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivityCategory', 'a')
            ->where('a.id = :id')
            ->setParameter('id', $id);
        $result = $qb->getQuery()->getResult();

        return count($result) > 0 ? $result[0] : null;
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return ActivityCategoryModel::class;
    }
}
