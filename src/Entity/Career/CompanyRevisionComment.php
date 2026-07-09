<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Application\AbstractRevisionComment;
use App\Repository\Career\CompanyRevisionCommentRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Override;

/**
 * A single message in the review discussion thread of a {@see CompanyRevision}.
 */
#[Entity(repositoryClass: CompanyRevisionCommentRepository::class)]
#[HasLifecycleCallbacks]
class CompanyRevisionComment extends AbstractRevisionComment
{
    #[ManyToOne(targetEntity: CompanyRevision::class)]
    #[JoinColumn(nullable: false)]
    private CompanyRevision $revision;

    #[Override]
    public function getRevision(): CompanyRevision
    {
        return $this->revision;
    }

    public function setRevision(CompanyRevision $revision): void
    {
        $this->revision = $revision;
    }
}
