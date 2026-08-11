<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisionInterface;
use App\Entity\Decision\OrganInformation;
use App\Entity\Decision\OrganInformationRevision;
use App\Entity\Decision\OrganInformationRevisionComment;
use App\Repository\Application\FindsRevisionCommentsTrait;
use App\Repository\Application\RevisionCommentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Override;

/**
 * @extends ServiceEntityRepository<OrganInformationRevisionComment>
 */
class OrganInformationRevisionCommentRepository extends ServiceEntityRepository implements
    RevisionCommentRepositoryInterface
{
    use FindsRevisionCommentsTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            OrganInformationRevisionComment::class,
        );
    }

    #[Override]
    public function supports(RevisionInterface $revision): bool
    {
        return $revision instanceof OrganInformationRevision;
    }

    /**
     * The whole review discussion across every revision of a body's page, oldest first.
     *
     * @return list<AbstractRevisionComment>
     */
    public function findThreadForOrganInformation(OrganInformation $information): array
    {
        return $this->findThread($information->getId());
    }

    #[Override]
    protected function revisionAggregateField(): string
    {
        return 'organInformation';
    }
}
