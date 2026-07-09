<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyRevisionComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VacancyRevisionComment>
 */
class VacancyRevisionCommentRepository extends ServiceEntityRepository
{
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
     * @return VacancyRevisionComment[]
     */
    public function findThreadForVacancy(Vacancy $vacancy): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect(
                'au',
                'acu',
                'r',
            )
            // A comment's author is a member's account OR a company user (mutually exclusive, both nullable), so both
            // must be LEFT-joined: an inner join on c.author alone silently drops every CompanyUser-authored comment.
            ->leftJoin(
                'c.author',
                'au',
            )
            ->leftJoin(
                'c.authorCompanyUser',
                'acu',
            )
            ->join(
                'c.revision',
                'r',
            )
            ->where('IDENTITY(r.vacancy) = :vacancyId')
            ->setParameter(
                'vacancyId',
                $vacancy->getId(),
                Types::INTEGER,
            )
            ->orderBy(
                'c.createdAt',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }
}
