<?php

declare(strict_types=1);

namespace App\Entity\Frontpage;

use App\Entity\Application\AbstractRevision;
use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisableInterface;
use App\Repository\Frontpage\PollRevisionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OrderBy;
use Override;

/**
 * What a member asked the association at one point in the chain: the question and the options it can be answered with.
 * The stable {@see Poll} keeps the votes, the discussion and when the question closes, so a poll that is running is
 * never rewritten underneath the members who already answered it.
 *
 * Unlike the other revisable domains a poll is never edited: there is no draft to pick back up and no cloner. A member
 * writes the question once and submits it in the same request, and a rejected question is asked again by writing a new
 * revision from scratch.
 */
#[Entity(repositoryClass: PollRevisionRepository::class)]
#[HasLifecycleCallbacks]
#[Index(
    name: 'poll_revision_chain_idx',
    columns: [
        'poll_id',
        'revisionNumber',
    ],
)]
class PollRevision extends AbstractRevision
{
    #[ManyToOne(
        targetEntity: Poll::class,
        inversedBy: 'revisions',
    )]
    #[JoinColumn(nullable: false)]
    private Poll $poll;

    #[ManyToOne(targetEntity: self::class)]
    #[JoinColumn(nullable: true)]
    private ?PollRevision $previousRevision = null;

    #[OneToOne(
        targetEntity: FrontpageLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'question_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private FrontpageLocalisedText $question;

    /** @var Collection<array-key, PollOption> */
    #[OneToMany(
        targetEntity: PollOption::class,
        mappedBy: 'revision',
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[OrderBy(['id' => 'ASC'])]
    private Collection $options;

    public function __construct()
    {
        $this->options = new ArrayCollection();

        // Which localised texts a revision has is its own business, and a form cannot bind to one that has none.
        // Doctrine does not run this when it hydrates a stored revision, so nothing is thrown away.
        $this->question = new FrontpageLocalisedText(
            null,
            null,
        );
    }

    #[Override]
    public function getRevisable(): RevisableInterface
    {
        return $this->poll;
    }

    /**
     * @return class-string<AbstractRevisionComment>
     */
    #[Override]
    public function getCommentClass(): string
    {
        return PollRevisionComment::class;
    }

    public function getPoll(): Poll
    {
        return $this->poll;
    }

    public function setPoll(Poll $poll): void
    {
        $this->poll = $poll;
    }

    #[Override]
    public function getPreviousRevision(): ?PollRevision
    {
        return $this->previousRevision;
    }

    public function setPreviousRevision(?PollRevision $previousRevision): void
    {
        $this->previousRevision = $previousRevision;
    }

    public function getQuestion(): FrontpageLocalisedText
    {
        return $this->question;
    }

    public function setQuestion(FrontpageLocalisedText $question): void
    {
        $this->question = $question;
    }

    /**
     * @return Collection<array-key, PollOption>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(PollOption $option): void
    {
        if ($this->options->contains($option)) {
            return;
        }

        $this->options->add($option);
        $option->setRevision($this);
    }

    public function removeOption(PollOption $option): void
    {
        $this->options->removeElement($option);
    }

    public function getTotalVotesCount(): int
    {
        $total = 0;

        foreach ($this->options as $option) {
            $total += $option->getVotesCount();
        }

        return $total;
    }
}
