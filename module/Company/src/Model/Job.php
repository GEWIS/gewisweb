<?php

namespace Company\Model;

use DateTime;
use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    ManyToOne,
    OneToMany,
};

/**
 * Job model.
 */
#[Entity]
class Job
{
    /**
     * The job id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * The job's display name.
     */
    #[Column(type: "string")]
    protected string $name;

    /**
     * The job's slug name.
     */
    #[Column(type: "string")]
    protected string $slugName;

    /**
     * The job's status.
     */
    #[Column(type: "boolean")]
    protected bool $active;

    /**
     * The job's website.
     */
    #[Column(type: "string")]
    protected string $website;

    /**
     * The location(url) of an attachment describing the job.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $attachment = null;

    /**
     * The job's contact's name.
     */
    #[Column(type: "string")]
    protected string $contactName;

    /**
     * The job's phone.
     */
    #[Column(type: "string")]
    protected string $phone;

    /**
     * The job's email.
     */
    #[Column(type: "string")]
    protected string $email;

    /**
     * The job's description.
     */
    #[Column(type: "text")]
    protected string $description;

    /**
     * The job's location.
     */
    #[Column(
        type: "text",
        nullable: true,
    )]
    protected ?string $location = null;

    /**
     * The job's timestamp.
     */
    #[Column(type: "date")]
    protected DateTime $timestamp;

    /**
     * The job's language.
     */
    #[Column(type: "string")]
    protected string $language;

    /**
     * The job's package.
     */
    #[ManyToOne(
        targetEntity: CompanyJobPackage::class,
        inversedBy: "jobs",
    )]
    protected CompanyJobPackage $package;

    /**
     * The job's category.
     */
    #[ManyToOne(targetEntity: JobCategory::class)]
    protected JobCategory $category;

    /**
     * The category id.
     */
    #[Column(type: "integer")]
    protected int $languageNeutralId;

    /**
     * Job labels.
     */
    #[OneToMany(
        targetEntity: JobLabelAssignment::class,
        mappedBy: "job",
        cascade: ["persist", "remove"],
        fetch: "EAGER",
    )]
    protected Collection $labels;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->labels = new ArrayCollection();
    }

    /**
     * Get's the id.
     *
     * @return int
     */
    public function getLanguageNeutralId(): int
    {
        $lnid = $this->languageNeutralId;
        if (0 == $lnid) {
            return $this->id;
        }

        return $lnid;
    }

    /**
     * Set's the id.
     *
     * @param int $languageNeutralId
     */
    public function setLanguageNeutralId(int $languageNeutralId): void
    {
        $this->languageNeutralId = $languageNeutralId;
    }

    /**
     * Get the job's id.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the job's name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the job's name.
     *
     * @param string $name
     */
    public function setName(string $name): void
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
    public function getActive(): bool
    {
        return $this->active;
    }

    public function isActive(): bool
    {
        return $this->getActive() && $this->getPackage()->isActive() && !$this->getPackage()->getCompany()->isHidden();
    }

    /**
     * Set the job's status.
     *
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * Get the job's website.
     *
     * @return string
     */
    public function getWebsite(): string
    {
        return $this->website;
    }

    /**
     * Set the job's website.
     *
     * @param string $website
     */
    public function setWebsite(string $website)
    {
        $this->website = $website;
    }

    /**
     * Get the job's attachment.
     *
     * @return string|null
     */
    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    /**
     * Set the job's attachment.
     *
     * @param string|null $attachment
     */
    public function setAttachment(?string $attachment): void
    {
        $this->attachment = $attachment;
    }

    /**
     * Get the job's contact's name.
     *
     * @return string
     */
    public function getContactName(): string
    {
        return $this->contactName;
    }

    /**
     * Set the job's contact's name.
     *
     * @param string $name
     */
    public function setContactName(string $name): void
    {
        $this->contactName = $name;
    }

    /**
     * Get the job's phone.
     *
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * Set the job's phone.
     *
     * @param string $phone
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * Get the job's email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set the job's email.
     *
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Get the job's timestamp.
     *
     * @return DateTime
     */
    public function getTimestamp(): DateTime
    {
        return $this->timestamp;
    }

    /**
     * Set the job's timestamp.
     *
     * @param DateTime $timestamp
     */
    public function setTimeStamp(DateTime $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Get the job's description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set the job's description.
     *
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Get the job's language.
     *
     * @return string language of the job
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Set the job's language.
     *
     * @param string $language language of the job
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
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
     * Sets all labels.
     *
     * @param array $labels
     */
    public function setLabels(array $labels): void
    {
        $this->labels->clear();

        foreach ($labels as $label) {
            $this->labels->add($label);
        }
    }

    /**
     * Adds a label.
     *
     * @param JobLabelAssignment $label
     */
    public function addLabel(JobLabelAssignment $label): void
    {
        if (null === $this->labels) {
            $this->labels = new ArrayCollection();
        }
        $label->setJob($this);
        $this->labels->add($label);
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
     * @return string|null
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * Sets the job's location.
     *
     * @param string|null $location
     */
    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }
}
