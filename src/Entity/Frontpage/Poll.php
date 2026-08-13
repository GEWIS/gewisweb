<?php

declare(strict_types=1);

namespace App\Entity\Frontpage;

use App\Entity\Application\LocalisedText as LocalisedTextModel;
use App\Entity\Application\RevisableInterface;
use App\Entity\Application\RevisionInterface;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Career\Company;
use App\Entity\Decision\Member as MemberModel;
use App\Entity\Decision\Organ;
use App\Repository\Frontpage\PollRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Override;
use RuntimeException;

/**
 * A question put to the members, which is a stable thing the votes and the discussion hang off while the question
 * itself lives on a chain of {@see PollRevision}s that the board has to agree to first.
 *
 * The expiry date is set by the reviewer rather than by whoever asked: approving a question is also scheduling it, and
 * the poll shown on the front page is the live one whose expiry date lies furthest into the future. A poll closes on
 * that date, so taking one down early is a matter of moving the date to today rather than deleting anything.
 *
 * @phpstan-import-type LocalisedTextGdprArrayType from LocalisedTextModel as ImportedLocalisedTextGdprArrayType
 * @phpstan-type PollGdprArrayType = array{
 *     id: ?int,
 *     expiryDate: ?string,
 *     question: ?ImportedLocalisedTextGdprArrayType,
 *     options: array<array-key, array{
 *         id: ?int,
 *         value: ImportedLocalisedTextGdprArrayType,
 *     }>,
 * }
 */
#[Entity(repositoryClass: PollRepository::class)]
class Poll implements RevisableInterface
{
    use IdentifiableTrait;

    #[Column(
        type: Types::DATE_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $expiryDate = null;

    /** @var Collection<array-key, PollRevision> */
    #[OneToMany(
        targetEntity: PollRevision::class,
        mappedBy: 'poll',
        cascade: ['persist'],
    )]
    #[OrderBy(['revisionNumber' => 'DESC'])]
    private Collection $revisions;

    #[ManyToOne(targetEntity: PollRevision::class)]
    #[JoinColumn(nullable: true)]
    private ?PollRevision $currentRevision = null;

    #[ManyToOne(targetEntity: PollRevision::class)]
    #[JoinColumn(nullable: true)]
    private ?PollRevision $liveRevision = null;

    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $votesAnonymisedAt = null;

    /** @var Collection<array-key, PollComment> */
    #[OneToMany(
        targetEntity: PollComment::class,
        mappedBy: 'poll',
        cascade: [
            'persist',
            'remove',
        ],
    )]
    #[OrderBy(['createdOn' => 'ASC'])]
    private Collection $comments;

    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    private MemberModel $creator;

    public function __construct()
    {
        $this->revisions = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getExpiryDate(): ?DateTime
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?DateTime $expiryDate): void
    {
        $this->expiryDate = $expiryDate;
    }

    public function getVotesAnonymisedAt(): ?DateTime
    {
        return $this->votesAnonymisedAt;
    }

    public function setVotesAnonymisedAt(?DateTime $votesAnonymisedAt): void
    {
        $this->votesAnonymisedAt = $votesAnonymisedAt;
    }

    /**
     * @return Collection<array-key, PollRevision>
     */
    #[Override]
    public function getRevisions(): Collection
    {
        return $this->revisions;
    }

    public function addRevision(PollRevision $revision): void
    {
        if ($this->revisions->contains($revision)) {
            return;
        }

        $this->revisions->add($revision);
        $revision->setPoll($this);
    }

    #[Override]
    public function getCurrentRevision(): ?PollRevision
    {
        return $this->currentRevision;
    }

    public function setCurrentRevision(?PollRevision $currentRevision): void
    {
        $this->currentRevision = $currentRevision;
    }

    #[Override]
    public function getLiveRevision(): ?PollRevision
    {
        return $this->liveRevision;
    }

    public function setLiveRevision(?PollRevision $liveRevision): void
    {
        $this->liveRevision = $liveRevision;
    }

    #[Override]
    public function markRevisionLive(RevisionInterface $revision): void
    {
        if (!$revision instanceof PollRevision) {
            throw new RuntimeException('A poll can only be made live by one of its own revisions.');
        }

        $this->setLiveRevision($revision);
    }

    #[Override]
    public function restoreLiveRevision(): void
    {
        $this->setCurrentRevision($this->getLiveRevision());
    }

    #[Override]
    public function getResourceId(): string
    {
        return 'poll';
    }

    /**
     * A question put to the whole association is the board's to agree to, and nobody else's.
     *
     * @inheritDoc
     */
    #[Override]
    public function getReviewerRoles(): array
    {
        return [];
    }

    /**
     * Anyone may ask a question, so a poll is never owned by a body.
     */
    #[Override]
    public function getResourceOrgan(): ?Organ
    {
        return null;
    }

    #[Override]
    public function getResourceCreator(): MemberModel
    {
        return $this->creator;
    }

    #[Override]
    public function getResourceCompany(): ?Company
    {
        return null;
    }

    public function getCreator(): MemberModel
    {
        return $this->creator;
    }

    public function setCreator(MemberModel $creator): void
    {
        $this->creator = $creator;
    }

    /**
     * @return Collection<array-key, PollComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * @return list<PollComment>
     */
    public function getTopLevelComments(): array
    {
        $comments = [];

        foreach ($this->comments as $comment) {
            if (null !== $comment->getParent()) {
                continue;
            }

            $comments[] = $comment;
        }

        return $comments;
    }

    public function addComment(PollComment $comment): void
    {
        if ($this->comments->contains($comment)) {
            return;
        }

        $this->comments->add($comment);
        $comment->setPoll($this);
    }

    public function getQuestion(): ?FrontpageLocalisedText
    {
        return $this->liveRevision?->getQuestion();
    }

    /**
     * @return Collection<array-key, PollOption>
     */
    public function getOptions(): Collection
    {
        return $this->liveRevision?->getOptions() ?? new ArrayCollection();
    }

    public function getTotalVotesCount(): int
    {
        return $this->liveRevision?->getTotalVotesCount() ?? 0;
    }

    /**
     * The answer currently ahead, or null when nothing is: no votes yet, or a tie at the top.
     */
    public function getLeadingOption(): ?PollOption
    {
        $leader = null;
        $tied = false;

        foreach ($this->getOptions() as $option) {
            $count = $option->getVotesCount();
            if (
                0 === $count
                || $count < ($leader?->getVotesCount() ?? 0)
            ) {
                continue;
            }

            if ($count === $leader?->getVotesCount()) {
                $tied = true;
                continue;
            }

            $leader = $option;
            $tied = false;
        }

        return $tied
            ? null
            : $leader;
    }

    /**
     * A poll closes on its expiry date, so one expiring today is already closed.
     */
    public function isActive(): bool
    {
        if (null === $this->liveRevision) {
            return false;
        }

        return null !== $this->expiryDate
            && $this->expiryDate > new DateTime('today');
    }

    /**
     * @return PollGdprArrayType
     */
    public function toGdprArray(): array
    {
        $options = [];
        $revision = $this->currentRevision;

        if (null !== $revision) {
            foreach ($revision->getOptions() as $option) {
                $options[] = [
                    'id' => $option->getId(),
                    'value' => $option->getText()->toGdprArray(),
                ];
            }
        }

        return [
            'id' => $this->getId(),
            'expiryDate' => $this->expiryDate?->format(DateTimeInterface::ATOM),
            'question' => $revision?->getQuestion()->toGdprArray(),
            'options' => $options,
        ];
    }
}
