<?php

declare(strict_types=1);

namespace App\Entity\Frontpage;

use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisionInterface;
use App\Repository\Frontpage\PollRevisionCommentRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Override;
use RuntimeException;

/**
 * A single message in the review discussion thread of a {@see PollRevision}, which is where the board says why a
 * question was turned down. Not to be confused with {@see PollComment}, which is what members write underneath a poll
 * that is running.
 */
#[Entity(repositoryClass: PollRevisionCommentRepository::class)]
#[HasLifecycleCallbacks]
class PollRevisionComment extends AbstractRevisionComment
{
    #[ManyToOne(targetEntity: PollRevision::class)]
    #[JoinColumn(nullable: false)]
    private PollRevision $revision;

    #[Override]
    public function getRevision(): PollRevision
    {
        return $this->revision;
    }

    public function setRevision(PollRevision $revision): void
    {
        $this->revision = $revision;
    }

    #[Override]
    public function attachTo(RevisionInterface $revision): void
    {
        if (!$revision instanceof PollRevision) {
            throw new RuntimeException('A comment on a poll can only belong to one of its own revisions.');
        }

        $this->setRevision($revision);
    }
}
