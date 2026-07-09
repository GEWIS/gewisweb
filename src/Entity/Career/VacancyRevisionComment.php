<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Application\AbstractRevisionComment;
use App\Repository\Career\VacancyRevisionCommentRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Override;

/**
 * A single message in the review discussion thread of a {@see VacancyRevision}.
 */
#[Entity(repositoryClass: VacancyRevisionCommentRepository::class)]
#[HasLifecycleCallbacks]
class VacancyRevisionComment extends AbstractRevisionComment
{
    #[ManyToOne(targetEntity: VacancyRevision::class)]
    #[JoinColumn(nullable: false)]
    private VacancyRevision $revision;

    #[Override]
    public function getRevision(): VacancyRevision
    {
        return $this->revision;
    }

    public function setRevision(VacancyRevision $revision): void
    {
        $this->revision = $revision;
    }
}
