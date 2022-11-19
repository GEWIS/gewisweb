<?php

namespace Education\Mapper;

use Application\Mapper\BaseMapper;
use Closure;
use Education\Model\Course as CourseModel;
use Education\Model\Exam as ExamModel;

/**
 * Mapper for Exam.
 */
class Exam extends BaseMapper
{
    public function findDocumentsByCourse(
        CourseModel $course,
        string $type,
    ): array {
        $qb = $this->getRepository()->createQueryBuilder('d');
        $qb->where('d.course = :course')
            ->andWhere('d.examType = :type')
            ->setParameter('course', $course)
            ->setParameter('type', $type);

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return ExamModel::class;
    }
}
