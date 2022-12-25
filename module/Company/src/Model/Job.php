<?php

namespace Company\Model;

use Application\Model\Traits\{
    ApprovableTrait,
    IdentifiableTrait,
    TimestampableTrait,
};
use Company\Model\Proposals\JobUpdate as JobUpdateProposal;
use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    HasLifecycleCallbacks,
    JoinColumn,
    JoinTable,
    ManyToMany,
    ManyToOne,
    OneToMany,
    OneToOne,
};
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Job model.
 */
#[Entity]
#[HasLifecycleCallbacks]
class Job implements ResourceInterface
{
    use IdentifiableTrait;
    use TimestampableTrait;
    use ApprovableTrait;

    /**
     * The job's slug name.
     */
    #[Column(type: "string")]
    protected string $slugName;

    /**
     * The job's status.
     */
    #[Column(type: "boolean")]
    protected bool $published;

    /**
     * The job's contact's name.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $contactName;

    /**
     * The job's phone.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $contactPhone;

    /**
     * The job's email.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $contactEmail;

    /**
     * The job's display name.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: "name_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected CompanyLocalisedText $name;

    /**
     * The job's location.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: "location_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected CompanyLocalisedText $location;

    /**
     * The job's website.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: "website_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected CompanyLocalisedText $website;

    /**
     * The job's description.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: "description_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected CompanyLocalisedText $description;

    /**
     * The location(url) of an attachment describing the job.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: "attachment_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected CompanyLocalisedText $attachment;

    /**
     * The job's package.
     */
    #[ManyToOne(
        targetEntity: CompanyJobPackage::class,
        inversedBy: "jobs",
    )]
    #[JoinColumn(
        name: "package_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected CompanyJobPackage $package;

    /**
     * The job's category.
     */
    #[ManyToOne(targetEntity: JobCategory::class)]
    #[JoinColumn(
        name: "category_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected JobCategory $category;

    /**
     * Job labels.
     */
    #[ManyToMany(
        targetEntity: JobLabel::class,
        inversedBy: "jobs",
        cascade: ["persist"],
    )]
    #[JoinTable(name: "JobLabelAssignment")]
    protected Collection $labels;

    /**
     * Proposed updates to this job.
     */
    #[OneToMany(
        targetEntity: JobUpdateProposal::class,
        mappedBy: "current",
        fetch: "EXTRA_LAZY",
    )]
    protected Collection $updateProposals;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->updateProposals = new ArrayCollection();
    }

    /**
     * Get the job's name.
     *
     * @return CompanyLocalisedText
     */
    public function getName(): CompanyLocalisedText
    {
        return $this->name;
    }

    /**
     * Set the job's name.
     *
     * @param CompanyLocalisedText $name
     */
    public function setName(CompanyLocalisedText $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the job's category.
     *
     * @return JobCategory
     */
    public function getCategory(): JobCategory
    {
        return $this->category;
    }

    /**
     * Set the job's category.
     *
     * @param JobCategory $category
     */
    public function setCategory(JobCategory $category): void
    {
        $this->category = $category;
    }

    /**
     * Get the job's slug name.
     *
     * @return string the Jobs slug name
     */
    public function getSlugName(): string
    {
        return $this->slugName;
    }

    /**
     * Set the job's slug name.
     *
     * @param string $name
     */
    public function setSlugName(string $name): void
    {
        $this->slugName = $name;
    }

    /**
     * Get the job's status.
     *
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->published;
    }

    public function isActive(): bool
    {
        return $this->isPublished()
            && $this->getPackage()->isActive()
            && !$this->getPackage()->getCompany()->isHidden();
    }

    /**
     * Set the job's status.
     *
     * @param bool $published
     */
    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    /**
     * Get the job's website.
     *
     * @return CompanyLocalisedText
     */
    public function getWebsite(): CompanyLocalisedText
    {
        return $this->website;
    }

    /**
     * Set the job's website.
     *
     * @param CompanyLocalisedText $website
     */
    public function setWebsite(CompanyLocalisedText $website): void
    {
        $this->website = $website;
    }

    /**
     * Get the job's attachment.
     *
     * @return CompanyLocalisedText
     */
    public function getAttachment(): CompanyLocalisedText
    {
        return $this->attachment;
    }

    /**
     * Set the job's attachment.
     *
     * @param CompanyLocalisedText $attachment
     */
    public function setAttachment(CompanyLocalisedText $attachment): void
    {
        $this->attachment = $attachment;
    }

    /**
     * Get the job's contact's name.
     *
     * @return string|null
     */
    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    /**
     * Set the job's contact's name.
     *
     * @param string|null $name
     */
    public function setContactName(?string $name): void
    {
        $this->contactName = $name;
    }

    /**
     * Get the job's contact's phone.
     *
     * @return string|null
     */
    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    /**
     * Set the job's contact's phone.
     *
     * @param string|null $contactPhone
     */
    public function setContactPhone(?string $contactPhone): void
    {
        $this->contactPhone = $contactPhone;
    }

    /**
     * Get the job's contact's email.
     *
     * @return string|null
     */
    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    /**
     * Set the job's contact's email.
     *
     * @param string|null $contactEmail
     */
    public function setContactEmail(?string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    /**
     * Get the job's description.
     *
     * @return CompanyLocalisedText
     */
    public function getDescription(): CompanyLocalisedText
    {
        return $this->description;
    }

    /**
     * Set the job's description.
     *
     * @param CompanyLocalisedText $description
     */
    public function setDescription(CompanyLocalisedText $description): void
    {
        $this->description = $description;
    }

    /**
     * Get the job's package.
     *
     * @return CompanyJobPackage
     */
    public function getPackage(): CompanyJobPackage
    {
        return $this->package;
    }

    /**
     * Get the job's company.
     *
     * @return Company
     */
    public function getCompany(): Company
    {
        return $this->getPackage()->getCompany();
    }

    /**
     * Get the labels. Returns an array of JobLabelAssignments.
     *
     * @return Collection
     */
    public function getLabels(): Collection
    {
        return $this->labels;
    }

    /**
     * @param array $labels
     */
    public function addLabels(array $labels): void
    {
        foreach ($labels as $label) {
            $this->addLabel($label);
        }
    }

    /**
     * @param JobLabel $label
     */
    public function addLabel(JobLabel $label): void
    {
        if ($this->labels->contains($label)) {
            return;
        }

        $this->labels->add($label);
        $label->addJob($this);
    }

    /**
     * @param array $labels
     */
    public function removeLabels(array $labels): void
    {
        foreach ($labels as $label) {
            $this->removeLabel($label);
        }
    }

    /**
     * @param JobLabel $label
     */
    public function removeLabel(JobLabel $label): void
    {
        if (!$this->labels->contains($label)) {
            return;
        }

        $this->labels->removeElement($label);
        $label->removeJob($this);
    }

    /**
     * @param CompanyJobPackage $package
     */
    public function setPackage(CompanyJobPackage $package): void
    {
        $this->package = $package;
    }

    /**
     * Returns the job's location.
     *
     * The location property specifies for which location (i.e. city or country)
     * this job is intended. This location may not be equal to the company's
     * address.
     *
     * @return CompanyLocalisedText
     */
    public function getLocation(): CompanyLocalisedText
    {
        return $this->location;
    }

    /**
     * Sets the job's location.
     *
     * @param CompanyLocalisedText $location
     */
    public function setLocation(CompanyLocalisedText $location): void
    {
        $this->location = $location;
    }

    /**
     * @return Collection
     */
    public function getUpdateProposals(): Collection
    {
        return $this->updateProposals;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $labelsArrays = [];
        foreach ($this->getLabels() as $label) {
            $labelsArrays[] = $label->toArray();
        }

        return [
            'slugName' => $this->getSlugName(),
            'category' => $this->getCategory(),
            'contactName' => $this->getContactName(),
            'contactEmail' => $this->getContactEmail(),
            'contactPhone' => $this->getContactPhone(),
            'published' => $this->isPublished(),
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
            'location' => $this->getLocation()->getValueNL(),
            'locationEn' => $this->getLocation()->getValueEN(),
            'website' => $this->getWebsite()->getValueNL(),
            'websiteEn' => $this->getWebsite()->getValueEN(),
            'description' => $this->getDescription()->getValueNL(),
            'descriptionEn' => $this->getDescription()->getValueEN(),
            'attachment' => $this->getAttachment()->getValueNL(),
            'attachmentEn' => $this->getAttachment()->getValueEN(),
            'labels' => $labelsArrays,
        ];
    }

    /**
     * @return string
     */
    public function getResourceId(): string
    {
        return 'job';
    }
}
