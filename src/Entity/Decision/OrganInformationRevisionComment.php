<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisionInterface;
use App\Repository\Decision\OrganInformationRevisionCommentRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Override;
use RuntimeException;

/**
 * A single message in the review discussion thread of an {@see OrganInformationRevision}.
 */
#[Entity(repositoryClass: OrganInformationRevisionCommentRepository::class)]
#[HasLifecycleCallbacks]
class OrganInformationRevisionComment extends AbstractRevisionComment
{
    #[ManyToOne(targetEntity: OrganInformationRevision::class)]
    #[JoinColumn(nullable: false)]
    private OrganInformationRevision $revision;

    #[Override]
    public function getRevision(): OrganInformationRevision
    {
        return $this->revision;
    }

    public function setRevision(OrganInformationRevision $revision): void
    {
        $this->revision = $revision;
    }

    #[Override]
    public function attachTo(RevisionInterface $revision): void
    {
        if (!$revision instanceof OrganInformationRevision) {
            throw new RuntimeException('A comment on a body can only belong to one of its own revisions.');
        }

        $this->setRevision($revision);
    }
}
