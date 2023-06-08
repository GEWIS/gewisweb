<?php

declare(strict_types=1);

namespace Education\Mapper;

use Application\Mapper\BaseMapper;
use Education\Model\Course as CourseModel;

/**
 * Mappers for Courses.
 *
 * @template-extends BaseMapper<CourseModel>
 */
class Course extends BaseMapper
{
    /**
     * Find a course by code.
     */
    public function findByCode(string $code): ?CourseModel
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('c, e')
            ->from($this->getRepositoryName(), 'c')
            ->where('c.code = ?1')
            ->leftJoin('c.documents', 'e');
        $qb->setParameter(1, $code);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Search for courses.
     *
     * @return CourseModel[]
     */
    public function search(string $query): array
    {
        $query = '%' . $query . '%';
        $qb = $this->getRepository()->createQueryBuilder('c');

        $qb->where('c.code LIKE ?1')
            ->orWhere('c.name LIKE ?1');
        $qb->setParameter(1, $query);

        return $qb->getQuery()->getResult();
    }

    protected function getRepositoryName(): string
    {
        return CourseModel::class;
    }
}
