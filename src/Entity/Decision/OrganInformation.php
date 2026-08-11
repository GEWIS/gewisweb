<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\RevisableInterface;
use App\Entity\Application\RevisionInterface;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\Career\Company;
use App\Entity\Decision\Member as MemberModel;
use App\Repository\Decision\OrganInformationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Override;
use RuntimeException;

/**
 * A body's page on the website, which is the one thing about a body that GEWIS itself writes: the organ and everything
 * about its installation comes from the decisions, and this is what its members get to say alongside that.
 *
 * The page is a stable aggregate with exactly one row per body; everything the members write lives on a chain of
 * {@see OrganInformationRevision}s, so the board sees what changed before the website does. What the public reads is
 * {@see self::getLiveRevision()}; what the body is working on is {@see self::getCurrentRevision()}.
 */
#[Entity(repositoryClass: OrganInformationRepository::class)]
#[HasLifecycleCallbacks]
#[UniqueConstraint(
    name: 'organ_information_organ_uniq',
    columns: ['organ_id'],
)]
class OrganInformation implements RevisableInterface
{
    use IdentifiableTrait;
    use TimestampableTrait;

    #[OneToOne(
        targetEntity: Organ::class,
        inversedBy: 'organInformation',
    )]
    #[JoinColumn(
        name: 'organ_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private Organ $organ;

    /**
     * The full chain of revisions, newest first.
     *
     * @var Collection<array-key, OrganInformationRevision>
     */
    #[OneToMany(
        targetEntity: OrganInformationRevision::class,
        mappedBy: 'organInformation',
        cascade: ['persist'],
    )]
    #[OrderBy(['revisionNumber' => 'DESC'])]
    private Collection $revisions;

    /**
     * The working head of the chain (the most recent revision, regardless of state).
     */
    #[ManyToOne(targetEntity: OrganInformationRevision::class)]
    #[JoinColumn(nullable: true)]
    private ?OrganInformationRevision $currentRevision = null;

    /**
     * The publicly live revision (the latest approved one), or null while the board has approved nothing.
     */
    #[ManyToOne(targetEntity: OrganInformationRevision::class)]
    #[JoinColumn(nullable: true)]
    private ?OrganInformationRevision $liveRevision = null;

    public function __construct()
    {
        $this->revisions = new ArrayCollection();
    }

    public function getOrgan(): Organ
    {
        return $this->organ;
    }

    public function setOrgan(Organ $organ): void
    {
        $this->organ = $organ;
    }

    /**
     * @return Collection<array-key, OrganInformationRevision>
     */
    #[Override]
    public function getRevisions(): Collection
    {
        return $this->revisions;
    }

    public function addRevision(OrganInformationRevision $revision): void
    {
        if ($this->revisions->contains($revision)) {
            return;
        }

        $this->revisions->add($revision);
        $revision->setOrganInformation($this);
    }

    #[Override]
    public function getCurrentRevision(): ?OrganInformationRevision
    {
        return $this->currentRevision;
    }

    public function setCurrentRevision(?OrganInformationRevision $currentRevision): void
    {
        $this->currentRevision = $currentRevision;
    }

    #[Override]
    public function getLiveRevision(): ?OrganInformationRevision
    {
        return $this->liveRevision;
    }

    public function setLiveRevision(?OrganInformationRevision $liveRevision): void
    {
        $this->liveRevision = $liveRevision;
    }

    #[Override]
    public function markRevisionLive(RevisionInterface $revision): void
    {
        if (!$revision instanceof OrganInformationRevision) {
            throw new RuntimeException('A body\'s page can only be made live by one of its own revisions.');
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
        return 'organ-information';
    }

    /**
     * Nobody but the board reviews what a body writes about itself, which is the whole point of the page going past
     * them.
     *
     * @inheritDoc
     */
    #[Override]
    public function getReviewerRoles(): array
    {
        return [];
    }

    /**
     * The body itself, which is what gives its installed members the right to edit its page.
     */
    #[Override]
    public function getResourceOrgan(): Organ
    {
        return $this->organ;
    }

    /**
     * A page belongs to a body rather than to whoever happened to write it, so there is no creator to defer to.
     */
    #[Override]
    public function getResourceCreator(): ?MemberModel
    {
        return null;
    }

    #[Override]
    public function getResourceCompany(): ?Company
    {
        return null;
    }

    /**
     * Whether anything the board approved is on the website yet.
     */
    public function isPublished(): bool
    {
        return null !== $this->liveRevision;
    }

    /**
     * Display proxy. The address the body is written to at, which is shown to members only.
     */
    public function getEmail(): ?string
    {
        return $this->liveRevision?->getEmail();
    }

    /**
     * Display proxy. The body's own website, if it keeps one.
     */
    public function getWebsite(): ?string
    {
        return $this->liveRevision?->getWebsite();
    }

    /**
     * Display proxy. The line or two that introduces the body on an overview card.
     */
    public function getShortDescription(): ?DecisionLocalisedText
    {
        return $this->liveRevision?->getShortDescription();
    }

    /**
     * Display proxy. What the body says about itself on its own page.
     */
    public function getDescription(): ?DecisionLocalisedText
    {
        return $this->liveRevision?->getDescription();
    }

    /**
     * Display proxy. The banner across the top of the body's page.
     */
    public function getBannerPath(): ?string
    {
        return $this->liveRevision?->getBannerPath();
    }

    /**
     * Display proxy. The image the body is shown by on an overview.
     */
    public function getLogoPath(): ?string
    {
        return $this->liveRevision?->getLogoPath();
    }

    /**
     * Display proxy. Where else the body can be followed.
     *
     * @return Collection<array-key, OrganSocialLink>
     */
    public function getSocialLinks(): Collection
    {
        return $this->liveRevision?->getSocialLinks() ?? new ArrayCollection();
    }
}
