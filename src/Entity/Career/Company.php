<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Application\RevisableInterface;
use App\Entity\Application\RevisionInterface;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\Career\Enums\CompanyPackageTypes;
use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Decision\Member as MemberModel;
use App\Entity\Decision\Organ as OrganModel;
use App\Entity\User\CompanyUser as CompanyUserModel;
use App\Repository\Career\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Override;
use RuntimeException;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use function array_filter;
use function array_map;
use function array_sum;
use function count;

/**
 * Company aggregate root.
 *
 * The stable identity, the name, slug, primary contact, packages and publication flag live here and survive
 * across edits. The revisable, reviewable content (localised texts, logo and contact details) lives on the chain of
 * {@see CompanyRevision}s. The publicly live version is {@see self::getLiveRevision()} (the latest approved revision);
 * the working head is {@see self::getCurrentRevision()}.
 */
#[Entity(repositoryClass: CompanyRepository::class)]
#[HasLifecycleCallbacks]
#[UniqueConstraint(
    name: 'company_slug_uniq',
    columns: ['slugName'],
)]
#[UniqueEntity(
    fields: ['slugName'],
    message: 'Another company already uses this slug.',
)]
class Company implements RevisableInterface
{
    use IdentifiableTrait;
    use TimestampableTrait;

    /**
     * The company's display name.
     */
    #[Column(type: Types::STRING)]
    private string $name;

    /**
     * The company's slug version of the name. (username).
     */
    #[Column(type: Types::STRING)]
    private string $slugName;

    /**
     * The representative the board writes to when it needs one answer from the company. Null when nobody has been
     * appointed yet, or when whoever held it was removed; the admin interface then asks for a new one.
     */
    #[ManyToOne(targetEntity: CompanyUserModel::class)]
    #[JoinColumn(
        nullable: true,
        onDelete: 'SET NULL',
    )]
    private ?CompanyUserModel $primaryContact = null;

    /**
     * Whether the company is published or not.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $published;

    /**
     * The full chain of revisions, newest first.
     *
     * @var Collection<array-key, CompanyRevision>
     */
    #[OneToMany(
        targetEntity: CompanyRevision::class,
        mappedBy: 'company',
        cascade: ['persist'],
    )]
    #[OrderBy(['revisionNumber' => 'DESC'])]
    private Collection $revisions;

    /**
     * The working head of the chain (the most recent revision, regardless of state).
     */
    #[ManyToOne(targetEntity: CompanyRevision::class)]
    #[JoinColumn(nullable: true)]
    private ?CompanyRevision $currentRevision = null;

    /**
     * The publicly live revision (the latest approved one), or null when nothing has been approved yet.
     */
    #[ManyToOne(targetEntity: CompanyRevision::class)]
    #[JoinColumn(nullable: true)]
    private ?CompanyRevision $liveRevision = null;

    /**
     * The company's packages.
     *
     * @var Collection<array-key, CompanyPackage>
     */
    #[OneToMany(
        targetEntity: CompanyPackage::class,
        mappedBy: 'company',
        cascade: [
            'persist',
            'remove',
        ],
    )]
    private Collection $packages;

    public function __construct()
    {
        $this->revisions = new ArrayCollection();
        $this->packages = new ArrayCollection();
    }

    /**
     * @return Collection<array-key, CompanyRevision>
     */
    #[Override]
    public function getRevisions(): Collection
    {
        return $this->revisions;
    }

    public function addRevision(CompanyRevision $revision): void
    {
        if ($this->revisions->contains($revision)) {
            return;
        }

        $this->revisions->add($revision);
        $revision->setCompany($this);
    }

    #[Override]
    public function getCurrentRevision(): ?CompanyRevision
    {
        return $this->currentRevision;
    }

    public function setCurrentRevision(?CompanyRevision $currentRevision): void
    {
        $this->currentRevision = $currentRevision;
    }

    #[Override]
    public function getLiveRevision(): ?CompanyRevision
    {
        return $this->liveRevision;
    }

    public function setLiveRevision(?CompanyRevision $liveRevision): void
    {
        $this->liveRevision = $liveRevision;
    }

    #[Override]
    public function markRevisionLive(RevisionInterface $revision): void
    {
        if (!$revision instanceof CompanyRevision) {
            throw new RuntimeException('A company can only be made live by one of its own revisions.');
        }

        $this->setLiveRevision($revision);
    }

    /**
     * The revision whose content is shown for this company: the live (approved) one when present, otherwise the
     * working head. Only ever null for a company with no revisions at all, which never occurs once persisted.
     */
    private function getDisplayRevision(): CompanyRevision
    {
        $revision = $this->liveRevision ?? $this->currentRevision;

        if (null === $revision) {
            throw new RuntimeException('Company has no revision to display.');
        }

        return $revision;
    }

    /**
     * Get the company's name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the company's name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Gets the company's slug name.
     *
     * @return string the company's slug name
     */
    public function getSlugName(): string
    {
        return $this->slugName;
    }

    /**
     * Sets the company's slug name.
     *
     * @param string $slugName the new slug name
     */
    public function setSlugName(string $slugName): void
    {
        $this->slugName = $slugName;
    }

    public function getPrimaryContact(): ?CompanyUserModel
    {
        return $this->primaryContact;
    }

    public function setPrimaryContact(?CompanyUserModel $primaryContact): void
    {
        $this->primaryContact = $primaryContact;
    }

    /**
     * A company can only be represented towards the board by one of its own people.
     */
    #[PrePersist]
    #[PreUpdate]
    public function assertPrimaryContactBelongsToCompany(): void
    {
        if (
            null === $this->primaryContact
            || $this->primaryContact->getCompany() === $this
        ) {
            return;
        }

        throw new RuntimeException('The primary contact of a company must be one of its own representatives.');
    }

    /**
     * Display proxy. Get the company's contact's name.
     */
    public function getContactName(): ?string
    {
        return $this->getDisplayRevision()->getContactName();
    }

    /**
     * Display proxy. Get the company's address.
     */
    public function getContactAddress(): ?string
    {
        return $this->getDisplayRevision()->getContactAddress();
    }

    /**
     * Display proxy. Get the company's email.
     */
    public function getContactEmail(): ?string
    {
        return $this->getDisplayRevision()->getContactEmail();
    }

    /**
     * Display proxy. Get the company's phone.
     */
    public function getContactPhone(): ?string
    {
        return $this->getDisplayRevision()->getContactPhone();
    }

    /**
     * Display proxy. Get the company's slogan.
     */
    public function getSlogan(): CareerLocalisedText
    {
        return $this->getDisplayRevision()->getSlogan();
    }

    /**
     * Display proxy. Get the company's logo.
     */
    public function getLogo(): ?string
    {
        return $this->getDisplayRevision()->getLogo();
    }

    /**
     * Display proxy. Get the company's description.
     */
    public function getDescription(): CareerLocalisedText
    {
        return $this->getDisplayRevision()->getDescription();
    }

    /**
     * Display proxy. Get the company's website.
     */
    public function getWebsite(): CareerLocalisedText
    {
        return $this->getDisplayRevision()->getWebsite();
    }

    /**
     * Return true if the company should not be visible to the user, and false if it should be visible to the user.
     */
    public function isHidden(): bool
    {
        // If the company has no live (approved) revision, it should never be shown.
        if (null === $this->getLiveRevision()) {
            return true;
        }

        $visible = false;

        // When any package is not expired, the company should be shown to the user
        foreach ($this->getPackages() as $package) {
            if ($package->isExpired()) {
                continue;
            }

            $visible = true;
        }

        // Except when it is explicitly marked as hidden.
        return !$visible || !$this->isPublished();
    }

    /**
     * Get the company's hidden status.
     */
    public function isPublished(): bool
    {
        return $this->published;
    }

    /**
     * Set the company's hidden status.
     */
    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    /**
     * Get the company's packages.
     *
     * @return Collection<array-key, CompanyPackage>
     */
    public function getPackages(): Collection
    {
        return $this->packages;
    }

    /**
     * Get the number of packages.
     *
     * @return int the number of packages
     */
    public function getNumberOfPackages(): int
    {
        return count($this->packages);
    }

    /**
     * Returns the number of jobs that are contained in all packages of this
     * company.
     */
    public function getNumberOfJobs(): int
    {
        $jobCount = static function (CompanyPackage $package): int {
            if ($package instanceof CompanyJobPackage) {
                return $package->getVacancies()->count();
            }

            return 0;
        };

        return array_sum(array_map($jobCount, $this->getPackages()->toArray()));
    }

    /**
     * Returns the number of jobs that are contained in all active packages of this
     * company.
     */
    public function getNumberOfActiveJobs(?VacancyCategories $category = null): int
    {
        $jobCount = static function (CompanyPackage $package) use ($category): int {
            if ($package instanceof CompanyJobPackage) {
                return $package->getNumberOfActiveJobs($category);
            }

            return 0;
        };

        return array_sum(array_map($jobCount, $this->getPackages()->toArray()));
    }

    /**
     * Returns the active vacancies of this company, optionally limited to a single category, gathered across all of the
     * company's job packages.
     *
     * @return Vacancy[]
     */
    public function getActiveVacancies(?VacancyCategories $category = null): array
    {
        $vacancies = [];

        foreach ($this->getPackages() as $package) {
            if (!$package instanceof CompanyJobPackage) {
                continue;
            }

            foreach ($package->getJobsInCategory($category) as $vacancy) {
                $vacancies[] = $vacancy;
            }
        }

        return $vacancies;
    }

    /**
     * Returns the number of expired packages.
     */
    public function getNumberOfExpiredPackages(): int
    {
        return count(
            array_filter(
                $this->getPackages()->toArray(),
                static function (CompanyPackage $package) {
                    return $package->isExpired();
                },
            ),
        );
    }

    /**
     * Returns true if company is featured.
     */
    public function isFeatured(): bool
    {
        $featuredPackages = array_filter(
            $this->getPackages()->toArray(),
            static function (CompanyPackage $package) {
                return CompanyPackageTypes::Featured === $package->getType() && $package->isActive();
            },
        );

        return [] !== $featuredPackages;
    }

    /**
     * Returns true if a banner is active, and false when there is no banner active.
     */
    public function isBannerActive(): bool
    {
        $banners = array_filter(
            $this->getPackages()->toArray(),
            static function (CompanyPackage $package) {
                return CompanyPackageTypes::Banner === $package->getType() && $package->isActive();
            },
        );

        return [] !== $banners;
    }

    /**
     * Returns the string identifier of the Resource.
     */
    #[Override]
    public function getResourceId(): string
    {
        return 'company';
    }

    /**
     * Companies are not owned by an organ.
     */
    #[Override]
    public function getResourceOrgan(): ?OrganModel
    {
        return null;
    }

    /**
     * Companies are not created by a member.
     */
    #[Override]
    public function getResourceCreator(): ?MemberModel
    {
        return null;
    }

    /**
     * A company owns itself, so its company users can edit it.
     */
    #[Override]
    public function getResourceCompany(): ?Company
    {
        return $this;
    }

    /**
     * Returns an array copy with all attributes.
     *
     * @return array{
     *     name: string,
     *     slugName: string,
     *     logo: ?string,
     *     contactName: ?string,
     *     contactEmail: ?string,
     *     contactAddress: ?string,
     *     contactPhone: ?string,
     *     published: bool,
     *     slogan: ?string,
     *     sloganEn: ?string,
     *     website: ?string,
     *     websiteEn: ?string,
     *     description: ?string,
     *     descriptionEn: ?string,
     * }
     */
    public function toArray(): array
    {
        $arraycopy = [];

        $arraycopy['name'] = $this->getName();
        $arraycopy['slugName'] = $this->getSlugName();

        $arraycopy['logo'] = $this->getLogo();
        $arraycopy['contactName'] = $this->getContactName();
        $arraycopy['contactEmail'] = $this->getContactEmail();
        $arraycopy['contactAddress'] = $this->getContactAddress();
        $arraycopy['contactPhone'] = $this->getContactPhone();
        $arraycopy['published'] = $this->isPublished();

        // Languages
        $arraycopy['slogan'] = $this->getSlogan()->getValueNL();
        $arraycopy['sloganEn'] = $this->getSlogan()->getValueEN();
        $arraycopy['website'] = $this->getWebsite()->getValueNL();
        $arraycopy['websiteEn'] = $this->getWebsite()->getValueEN();
        $arraycopy['description'] = $this->getDescription()->getValueNL();
        $arraycopy['descriptionEn'] = $this->getDescription()->getValueEN();

        return $arraycopy;
    }
}
