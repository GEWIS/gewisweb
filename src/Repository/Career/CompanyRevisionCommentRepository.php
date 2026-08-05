<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisionInterface;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\CompanyRevisionComment;
use App\Repository\Application\FindsRevisionCommentsTrait;
use App\Repository\Application\RevisionCommentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Override;

/**
 * @extends ServiceEntityRepository<CompanyRevisionComment>
 */
class CompanyRevisionCommentRepository extends ServiceEntityRepository implements RevisionCommentRepositoryInterface
{
    use FindsRevisionCommentsTrait;

    #[Override]
    public function supports(RevisionInterface $revision): bool
    {
        return $revision instanceof CompanyRevision;
    }

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CompanyRevisionComment::class,
        );
    }

    /**
     * The full review discussion across every revision of a company, oldest first.
     *
     * @return list<AbstractRevisionComment>
     */
    public function findThreadForCompany(Company $company): array
    {
        return $this->findThread($company->getId());
    }

    #[Override]
    protected function revisionAggregateField(): string
    {
        return 'company';
    }
}
