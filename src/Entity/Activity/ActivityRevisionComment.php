<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisionInterface;
use App\Repository\Activity\ActivityRevisionCommentRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Override;
use RuntimeException;

/**
 * A single message in the review discussion thread of an {@see ActivityRevision}.
 */
#[Entity(repositoryClass: ActivityRevisionCommentRepository::class)]
#[HasLifecycleCallbacks]
class ActivityRevisionComment extends AbstractRevisionComment
{
    #[ManyToOne(targetEntity: ActivityRevision::class)]
    #[JoinColumn(nullable: false)]
    private ActivityRevision $revision;

    #[Override]
    public function getRevision(): ActivityRevision
    {
        return $this->revision;
    }

    public function setRevision(ActivityRevision $revision): void
    {
        $this->revision = $revision;
    }

    #[Override]
    public function attachTo(RevisionInterface $revision): void
    {
        if (!$revision instanceof ActivityRevision) {
            throw new RuntimeException('A comment on an activity can only belong to one of its own revisions.');
        }

        $this->setRevision($revision);
    }
}
