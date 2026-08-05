<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisionInterface;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyRevision;
use App\Entity\Career\VacancyRevisionComment;
use App\Repository\Application\FindsRevisionCommentsTrait;
use App\Repository\Application\RevisionCommentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Override;

/**
 * @extends ServiceEntityRepository<VacancyRevisionComment>
 */
class VacancyRevisionCommentRepository extends ServiceEntityRepository implements RevisionCommentRepositoryInterface
{
    use FindsRevisionCommentsTrait;

    #[Override]
    public function supports(RevisionInterface $revision): bool
    {
        return $revision instanceof VacancyRevision;
    }

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            VacancyRevisionComment::class,
        );
    }

    /**
     * The full review discussion across every revision of a vacancy, oldest first.
     *
     * @return list<AbstractRevisionComment>
     */
    public function findThreadForVacancy(Vacancy $vacancy): array
    {
        return $this->findThread($vacancy->getId());
    }

    #[Override]
    protected function revisionAggregateField(): string
    {
        return 'vacancy';
    }
}
