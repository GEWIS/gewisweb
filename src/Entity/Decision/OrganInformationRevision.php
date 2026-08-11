<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\AbstractRevision;
use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\Enums\SocialPlatform;
use App\Entity\Application\RevisableInterface;
use App\Entity\Application\Traits\HasSocialLinksTrait;
use App\Repository\Decision\OrganInformationRevisionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Override;

/**
 * Everything a body says about itself at one point in the chain: the descriptions, how to reach it, the images its page
 * and its card are shown by, and where else it can be followed. The stable {@see OrganInformation} keeps only the
 * identity and which revision is which, so a change to any of this goes past the board before the website shows it.
 *
 * Each image is kept twice over: the original that was uploaded, so the crop can be adjusted again without asking for
 * the file a second time, and the cropped result that is actually served. The crop itself is stored as fractions of the
 * original, which means it survives the original being served at any size.
 */
#[Entity(repositoryClass: OrganInformationRevisionRepository::class)]
#[HasLifecycleCallbacks]
class OrganInformationRevision extends AbstractRevision
{
    use HasSocialLinksTrait;

    #[ManyToOne(
        targetEntity: OrganInformation::class,
        inversedBy: 'revisions',
    )]
    #[JoinColumn(nullable: false)]
    private OrganInformation $organInformation;

    /**
     * The revision this one supersedes (null for the first revision in the chain).
     */
    #[ManyToOne(targetEntity: self::class)]
    #[JoinColumn(nullable: true)]
    private ?OrganInformationRevision $previousRevision = null;

    /**
     * The line or two that introduces the body on an overview card.
     */
    #[OneToOne(
        targetEntity: DecisionLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'shortDescription_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private DecisionLocalisedText $shortDescription;

    /**
     * What the body has to say about itself on its own page, as markdown.
     */
    #[OneToOne(
        targetEntity: DecisionLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'description_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private DecisionLocalisedText $description;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $email = null;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $website = null;

    /**
     * The image as it was uploaded, kept so the crop can be adjusted later.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $bannerSource = null;

    /**
     * The part of the source the cover shows, as fractions of the source.
     *
     * @var array<string, float>|null
     */
    #[Column(
        type: Types::JSON,
        nullable: true,
    )]
    private ?array $bannerCrop = null;

    /**
     * The cropped cover, which is what the page is served.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $bannerPath = null;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $logoSource = null;

    /**
     * The part of the source the thumbnail shows, as fractions of the source.
     *
     * @var array<string, float>|null
     */
    #[Column(
        type: Types::JSON,
        nullable: true,
    )]
    private ?array $logoCrop = null;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $logoPath = null;

    /** @var Collection<array-key, OrganSocialLink> */
    #[OneToMany(
        targetEntity: OrganSocialLink::class,
        mappedBy: 'revision',
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    private Collection $socialLinks;

    public function __construct()
    {
        $this->socialLinks = new ArrayCollection();

        // Which localised texts a revision has is its own business, and a form cannot bind to one that has none.
        // Doctrine does not run this when it hydrates a stored revision, so nothing is thrown away.
        $this->shortDescription = new DecisionLocalisedText(
            null,
            null,
        );
        $this->description = new DecisionLocalisedText(
            null,
            null,
        );
    }

    #[Override]
    public function getRevisable(): RevisableInterface
    {
        return $this->organInformation;
    }

    /**
     * @return class-string<AbstractRevisionComment>
     */
    #[Override]
    public function getCommentClass(): string
    {
        return OrganInformationRevisionComment::class;
    }

    public function getOrganInformation(): OrganInformation
    {
        return $this->organInformation;
    }

    public function setOrganInformation(OrganInformation $organInformation): void
    {
        $this->organInformation = $organInformation;
    }

    /**
     * The body this revision describes, which the aggregate holds because it never changes.
     */
    public function getOrgan(): Organ
    {
        return $this->organInformation->getOrgan();
    }

    #[Override]
    public function getPreviousRevision(): ?OrganInformationRevision
    {
        return $this->previousRevision;
    }

    public function setPreviousRevision(?OrganInformationRevision $previousRevision): void
    {
        $this->previousRevision = $previousRevision;
    }

    public function getShortDescription(): DecisionLocalisedText
    {
        return $this->shortDescription;
    }

    public function setShortDescription(DecisionLocalisedText $shortDescription): void
    {
        $this->shortDescription = $shortDescription;
    }

    public function getDescription(): DecisionLocalisedText
    {
        return $this->description;
    }

    public function setDescription(DecisionLocalisedText $description): void
    {
        $this->description = $description;
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

    public function getBannerSource(): ?string
    {
        return $this->bannerSource;
    }

    public function setBannerSource(?string $bannerSource): void
    {
        $this->bannerSource = $bannerSource;
    }

    /**
     * @return array<string, float>|null
     */
    public function getBannerCrop(): ?array
    {
        return $this->bannerCrop;
    }

    /**
     * @param array<string, float>|null $bannerCrop
     */
    public function setBannerCrop(?array $bannerCrop): void
    {
        $this->bannerCrop = $bannerCrop;
    }

    public function getBannerPath(): ?string
    {
        return $this->bannerPath;
    }

    public function setBannerPath(?string $bannerPath): void
    {
        $this->bannerPath = $bannerPath;
    }

    public function getLogoSource(): ?string
    {
        return $this->logoSource;
    }

    public function setLogoSource(?string $logoSource): void
    {
        $this->logoSource = $logoSource;
    }

    /**
     * @return array<string, float>|null
     */
    public function getLogoCrop(): ?array
    {
        return $this->logoCrop;
    }

    /**
     * @param array<string, float>|null $logoCrop
     */
    public function setLogoCrop(?array $logoCrop): void
    {
        $this->logoCrop = $logoCrop;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function setLogoPath(?string $logoPath): void
    {
        $this->logoPath = $logoPath;
    }

    /**
     * @return Collection<array-key, OrganSocialLink>
     */
    #[Override]
    public function getSocialLinks(): Collection
    {
        return $this->socialLinks;
    }

    #[Override]
    protected function newSocialLink(SocialPlatform $platform): OrganSocialLink
    {
        $link = new OrganSocialLink($platform);
        $link->setRevision($this);

        return $link;
    }
}
