<?php

namespace Education\Mapper;

use Application\Mapper\BaseMapper;
use Education\Model\Course as CourseModel;

/**
 * Mappers for Course.
 *
 * NOTE: Organs will be modified externally by a script. Modifications will be
 * overwritten.
 */
class Course extends BaseMapper
{
    /**
     * Find a course by code.
     *
     * @param string $code
     *
     * @return CourseModel|null
     */
    public function findByCode(string $code): ?CourseModel
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('c, e, p, ch, ce')
            ->from($this->getRepositoryName(), 'c')
            ->where('c.code = ?1')
            ->leftJoin('c.exams', 'e')
            ->leftJoin('c.parent', 'p')
            ->leftJoin('c.children', 'ch')
            ->leftJoin('ch.exams', 'ce');
        $qb->setParameter(1, $code);

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Search for courses.
     *
     * @param string $query
     *
     * @return array
     */
    public function search(string $query): array
    {
        $query = '%' . $query . '%';
        $qb = $this->em->createQueryBuilder();

        $qb->select('c')
            ->from($this->getRepositoryName(), 'c')
            ->where('c.code LIKE ?1')
            ->orWhere('c.name LIKE ?1');
        $qb->setParameter(1, $query);

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return CourseModel::class;
    }
}
