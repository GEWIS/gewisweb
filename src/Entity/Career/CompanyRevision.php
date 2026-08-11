<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Application\AbstractRevision;
use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\Enums\SocialPlatform;
use App\Entity\Application\RevisableInterface;
use App\Entity\Application\Traits\HasSocialLinksTrait;
use App\Repository\Career\CompanyRevisionRepository;
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
 * An immutable snapshot of a {@see Company}'s revisable content for one point in its revision chain. The stable
 * {@see Company} owns its name, slug, representative details, packages and publication flag; everything that may be
 * revised and reviewed (the localised texts, the logo and the contact details) lives here.
 */
#[Entity(repositoryClass: CompanyRevisionRepository::class)]
#[HasLifecycleCallbacks]
class CompanyRevision extends AbstractRevision
{
    use HasSocialLinksTrait;

    /**
     * The company this revision belongs to.
     */
    #[ManyToOne(
        targetEntity: Company::class,
        inversedBy: 'revisions',
    )]
    #[JoinColumn(nullable: false)]
    private Company $company;

    /**
     * The revision this one supersedes (null for the first revision in the chain).
     */
    #[ManyToOne(targetEntity: self::class)]
    #[JoinColumn(nullable: true)]
    private ?CompanyRevision $previousRevision = null;

    #[OneToOne(
        targetEntity: CareerLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'slogan_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private CareerLocalisedText $slogan;

    #[OneToOne(
        targetEntity: CareerLocalisedText::class,
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
    private CareerLocalisedText $description;

    #[OneToOne(
        targetEntity: CareerLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'website_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private CareerLocalisedText $website;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $squareLogo = null;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $bannerLogo = null;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $contactName = null;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $contactAddress = null;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $contactEmail = null;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $contactPhone = null;

    /**
     * Where else this company can be followed. Owned by the revision, so adding or dropping one is reviewed like
     * anything else the profile says.
     *
     * @var Collection<array-key, CompanySocialLink>
     */
    #[OneToMany(
        targetEntity: CompanySocialLink::class,
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
        $this->slogan = new CareerLocalisedText(
            null,
            null,
        );
        $this->description = new CareerLocalisedText(
            null,
            null,
        );
        $this->website = new CareerLocalisedText(
            null,
            null,
        );
    }

    #[Override]
    public function getRevisable(): RevisableInterface
    {
        return $this->company;
    }

    /**
     * @return class-string<AbstractRevisionComment>
     */
    #[Override]
    public function getCommentClass(): string
    {
        return CompanyRevisionComment::class;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    #[Override]
    public function getPreviousRevision(): ?CompanyRevision
    {
        return $this->previousRevision;
    }

    public function setPreviousRevision(?CompanyRevision $previousRevision): void
    {
        $this->previousRevision = $previousRevision;
    }

    public function getSlogan(): CareerLocalisedText
    {
        return $this->slogan;
    }

    public function setSlogan(CareerLocalisedText $slogan): void
    {
        $this->slogan = $slogan;
    }

    public function getDescription(): CareerLocalisedText
    {
        return $this->description;
    }

    public function setDescription(CareerLocalisedText $description): void
    {
        $this->description = $description;
    }

    public function getWebsite(): CareerLocalisedText
    {
        return $this->website;
    }

    public function setWebsite(CareerLocalisedText $website): void
    {
        $this->website = $website;
    }

    public function getSquareLogo(): ?string
    {
        return $this->squareLogo;
    }

    public function setSquareLogo(?string $squareLogo): void
    {
        $this->squareLogo = $squareLogo;
    }

    public function getBannerLogo(): ?string
    {
        return $this->bannerLogo;
    }

    public function setBannerLogo(?string $bannerLogo): void
    {
        $this->bannerLogo = $bannerLogo;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): void
    {
        $this->contactName = $contactName;
    }

    public function getContactAddress(): ?string
    {
        return $this->contactAddress;
    }

    public function setContactAddress(?string $contactAddress): void
    {
        $this->contactAddress = $contactAddress;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    /**
     * @return Collection<array-key, CompanySocialLink>
     */
    #[Override]
    public function getSocialLinks(): Collection
    {
        return $this->socialLinks;
    }

    #[Override]
    protected function newSocialLink(SocialPlatform $platform): CompanySocialLink
    {
        $link = new CompanySocialLink($platform);
        $link->setRevision($this);

        return $link;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): void
    {
        $this->contactPhone = $contactPhone;
    }
}
