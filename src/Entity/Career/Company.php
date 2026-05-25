<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Application\Traits\ApprovableTrait;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\Application\Traits\UpdateProposableTrait;
use App\Entity\Career\Enums\CompanyPackageTypes;
use App\Entity\Career\JobCategory as JobCategoryModel;
use App\Entity\Career\Proposals\CompanyUpdate as CompanyUpdateProposal;
use App\Repository\Career\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Exception;

use function array_filter;
use function array_map;
use function array_sum;
use function boolval;
use function count;

/**
 * Company model.
 */
#[Entity(repositoryClass: CompanyRepository::class)]
#[HasLifecycleCallbacks]
class Company
{
    use ApprovableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;
    use UpdateProposableTrait;

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
     * The name of the person representing the company. Is used for communications with the company.
     */
    #[Column(type: Types::STRING)]
    private string $representativeName;

    /**
     * The email address of the person representing the company. Is used for communications with the company.
     */
    #[Column(type: Types::STRING)]
    private string $representativeEmail;

    /**
     * The company's contact's name.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $contactName;

    /**
     * The company's contact address.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $contactAddress;

    /**
     * The company's contact email address.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $contactEmail;

    /**
     * The company's contact phone.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $contactPhone;

    /**
     * Company slogan.
     */
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

    /**
     * Company logo.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $logo = null;

    /**
     * Company description.
     */
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

    /**
     * Company website.
     */
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

    /**
     * Whether the company is published or not.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $published;

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

    /**
     * Proposed updates to this company.
     *
     * @var Collection<array-key, CompanyUpdateProposal>
     */
    #[OneToMany(
        targetEntity: CompanyUpdateProposal::class,
        mappedBy: 'original',
        fetch: 'EXTRA_LAZY',
    )]
    private Collection $updateProposals;

    public function __construct()
    {
        $this->packages = new ArrayCollection();
        $this->updateProposals = new ArrayCollection();
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

    /**
     * Get the name of the person representing the company.
     */
    public function getRepresentativeName(): string
    {
        return $this->representativeName;
    }

    /**
     * Set the name of the person representing the company.
     */
    public function setRepresentativeName(string $name): void
    {
        $this->representativeName = $name;
    }

    /**
     * Get the email address of the person representing the company.
     */
    public function getRepresentativeEmail(): string
    {
        return $this->representativeEmail;
    }

    /**
     * Set the email address of the person representing the company.
     */
    public function setRepresentativeEmail(string $email): void
    {
        $this->representativeEmail = $email;
    }

    /**
     * Get the company's contact's name.
     */
    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    /**
     * Set the company's contact's name.
     */
    public function setContactName(?string $name): void
    {
        $this->contactName = $name;
    }

    /**
     * Get the company's address.
     */
    public function getContactAddress(): ?string
    {
        return $this->contactAddress;
    }

    /**
     * Set the company's address.
     */
    public function setContactAddress(?string $contactAddress): void
    {
        $this->contactAddress = $contactAddress;
    }

    /**
     * Get the company's email.
     */
    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    /**
     * Set the company's email.
     */
    public function setContactEmail(?string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    /**
     * Get the company's phone.
     */
    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    /**
     * Set the company's phone.
     */
    public function setContactPhone(?string $contactPhone): void
    {
        $this->contactPhone = $contactPhone;
    }

    /**
     * Get the company's slogan.
     */
    public function getSlogan(): CareerLocalisedText
    {
        return $this->slogan;
    }

    /**
     * Set the company's slogan.
     */
    public function setSlogan(CareerLocalisedText $slogan): void
    {
        $this->slogan = $slogan;
    }

    /**
     * Get the company's logo.
     */
    public function getLogo(): ?string
    {
        return $this->logo;
    }

    /**
     * Set the company's logo.
     */
    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }

    /**
     * Get the company's description.
     */
    public function getDescription(): CareerLocalisedText
    {
        return $this->description;
    }

    /**
     * Set the company's description.
     */
    public function setDescription(CareerLocalisedText $description): void
    {
        $this->description = $description;
    }

    /**
     * Get the company's website.
     */
    public function getWebsite(): CareerLocalisedText
    {
        return $this->website;
    }

    /**
     * Set the company's description.
     */
    public function setWebsite(CareerLocalisedText $website): void
    {
        $this->website = $website;
    }

    /**
     * Return true if the company should not be visible to the user, and false if it should be visible to the user.
     */
    public function isHidden(): bool
    {
        // If the company is not approved, it should never be shown.
        if (!$this->isApproved()) {
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
     * @return Collection<array-key, CompanyUpdateProposal>
     */
    public function getUpdateProposals(): Collection
    {
        return $this->updateProposals;
    }

    /**
     * Returns the number of jobs that are contained in all packages of this
     * company.
     */
    public function getNumberOfJobs(): int
    {
        $jobCount = static function (CompanyPackage $package): int {
            if ($package instanceof CompanyJobPackage) {
                return $package->getJobsWithoutProposals()->count();
            }

            return 0;
        };

        return array_sum(array_map($jobCount, $this->getPackages()->toArray()));
    }

    /**
     * Returns the number of jobs that are contained in all active packages of this
     * company.
     */
    public function getNumberOfActiveJobs(?JobCategoryModel $category = null): int
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
     * Updates this object with values in the form of getArrayCopy(). This does not include the logo.
     *
     * @psalm-param array{
     *     name: string,
     *     slugName: string,
     *     representativeName: string,
     *     representativeEmail: string,
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
     * } $data
     *
     * @throws Exception
     */
    public function exchangeArray(array $data): void
    {
        $this->setName($data['name']);
        $this->setSlugName($data['slugName']);

        $this->setRepresentativeName($data['representativeName']);
        $this->setRepresentativeEmail($data['representativeEmail']);

        $this->setContactName($data['contactName']);
        $this->setContactAddress($data['contactAddress']);
        $this->setContactEmail($data['contactEmail']);
        $this->setContactPhone($data['contactPhone']);
        $this->setPublished(boolval($data['published']));

        $this->getSlogan()->updateValues(
            $data['sloganEn'],
            $data['slogan'],
        );
        $this->getWebsite()->updateValues(
            $data['websiteEn'],
            $data['website'],
        );
        $this->getDescription()->updateValues(
            $data['descriptionEn'],
            $data['description'],
        );
    }

    /**
     * Returns an array copy with all attributes.
     *
     * @return array{
     *     name: string,
     *     slugName: string,
     *     representativeName: string,
     *     representativeEmail: string,
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

        $arraycopy['representativeName'] = $this->getRepresentativeName();
        $arraycopy['representativeEmail'] = $this->getRepresentativeEmail();

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

    public function getResourceId(): string
    {
        return 'company';
    }
}
