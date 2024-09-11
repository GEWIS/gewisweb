<?php

declare(strict_types=1);

namespace Decision\Model;

use Application\Model\Traits\ApprovableTrait;
use Application\Model\Traits\IdentifiableTrait;
use Application\Model\Traits\TimestampableTrait;
use Application\Model\Traits\UpdateProposableTrait;
use Decision\Model\Proposals\OrganInformationUpdate as OrganInformationUpdateProposalModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use User\Permissions\Resource\OrganResourceInterface;

/**
 * Organ information.
 */
#[Entity]
class OrganInformation implements OrganResourceInterface
{
    use IdentifiableTrait;
    use TimestampableTrait;
    use ApprovableTrait;
    /** @use UpdateProposableTrait<OrganInformationUpdateProposalModel> */
    use UpdateProposableTrait;

    #[ManyToOne(
        targetEntity: Organ::class,
        inversedBy: 'organInformation',
    )]
    #[JoinColumn(
        name: 'organ_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected Organ $organ;

    /**
     * The email address of the organ if available.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $email = null;

    /**
     * The website of the organ if available.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $website = null;

    /**
     * The short description of the organ which is shown in cards.
     */
    #[OneToOne(
        targetEntity: DecisionLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'tagline_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected DecisionLocalisedText $tagline;

    /**
     * The full description of the organ.
     */
    #[OneToOne(
        targetEntity: DecisionLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'description_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected DecisionLocalisedText $description;

    /**
     * The cover photo to display for this organ.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $coverPath = null;

    /**
     * The thumbnail photo to display for this organ.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $thumbnailPath = null;

    /**
     * Proposed updates to this organ information
     *
     * @var Collection<array-key, OrganInformationUpdateProposalModel>
     */
    #[OneToMany(
        targetEntity: OrganInformationUpdateProposalModel::class,
        mappedBy: 'original',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
        fetch: 'EXTRA_LAZY',
    )]
    protected Collection $updateProposals;

    public function __construct()
    {
        $this->updateProposals = new ArrayCollection();
    }

    public function getOrgan(): Organ
    {
        return $this->organ;
    }

    public function setOrgan(Organ $organ): void
    {
        $this->organ = $organ;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }

    public function getTagline(): DecisionLocalisedText
    {
        return $this->tagline;
    }

    public function setTagline(DecisionLocalisedText $tagline): void
    {
        $this->tagline = $tagline;
    }

    public function getDescription(): DecisionLocalisedText
    {
        return $this->description;
    }

    public function setDescription(DecisionLocalisedText $description): void
    {
        $this->description = $description;
    }

    public function getCoverPath(): ?string
    {
        return $this->coverPath;
    }

    public function setCoverPath(?string $coverPath): void
    {
        $this->coverPath = $coverPath;
    }

    public function getThumbnailPath(): ?string
    {
        return $this->thumbnailPath;
    }

    public function setThumbnailPath(?string $thumbnailPath): void
    {
        $this->thumbnailPath = $thumbnailPath;
    }

    /**
     * @return Collection<array-key, OrganInformationUpdateProposalModel>
     */
    public function getUpdateProposals(): Collection
    {
        return $this->updateProposals;
    }

    public function __clone()
    {
        $this->id = null;
    }

    public function getResourceOrgan(): ?Organ
    {
        return $this->getOrgan();
    }

    public function getResourceId(): string
    {
        return 'organInformation';
    }
}
