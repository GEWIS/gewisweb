<?php

declare(strict_types=1);

namespace Education\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Member as MemberModel;
use Education\Model\Course as CourseModel;
use Education\Model\CourseDocument as CourseDocumentModel;
use Education\Model\Exam as ExamModel;
use Education\Model\Summary as SummaryModel;

use function addcslashes;

/**
 * Mapper for course documents.
 *
 * @template-extends BaseMapper<CourseDocumentModel>
 */
class CourseDocument extends BaseMapper
{
    /**
     * @psalm-param class-string<ExamModel>|class-string<SummaryModel> $type
     *
     * @return CourseDocumentModel[]
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
     * Get all summaries created by a specific member.
     *
     * @return SummaryModel[]
     */
    public function findSummariesByAuthor(MemberModel $member): array
    {
        $qb = $this->getEntityManager()->getRepository(SummaryModel::class)->createQueryBuilder('d');
        $qb->where('d.author LIKE :full_name')
            ->setParameter('full_name', '%' . addcslashes($member->getFullName(), '%_') . '%');

        return $qb->getQuery()->getResult();
    }

    protected function getRepositoryName(): string
    {
        return CourseDocumentModel::class;
    }
}
