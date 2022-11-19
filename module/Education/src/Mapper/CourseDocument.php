<?php

namespace Education\Mapper;

use Application\Mapper\BaseMapper;
use Education\Model\{
    Course as CourseModel,
    CourseDocument as CourseDocumentModel,
    Exam as ExamModel,
    Summary as SummaryModel,
};

/**
 * Mapper for Exam.
 */
class CourseDocument extends BaseMapper
{
    /**
     * @psalm-param class-string<ExamModel>|class-string<SummaryModel> $type
     */
    public function findDocumentsByCourse(
        CourseModel $course,
        string $type,
    ): array {
        $qb = $this->getRepository()->createQueryBuilder('d');
        $qb->where('d.course = :course')
            ->andWhere('d INSTANCE OF :type')
            ->setParameter('course', $course)
            ->setParameter('type', $this->getEntityManager()->getClassMetadata($type));

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return CourseDocumentModel::class;
    }
}
