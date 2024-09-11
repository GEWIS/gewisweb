<?php

declare(strict_types=1);

namespace Company\Model;

use Application\Model\Traits\ApprovableTrait;
use Application\Model\Traits\IdentifiableTrait;
use Application\Model\Traits\TimestampableTrait;
use Application\Model\Traits\UpdateProposableTrait;
use Company\Model\Proposals\JobUpdate as JobUpdateProposalModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Job model.
 *
 * @psalm-import-type JobLabelArrayType from JobLabel as ImportedJobLabelArrayType
 */
#[Entity]
#[HasLifecycleCallbacks]
class Job implements ResourceInterface
{
    use IdentifiableTrait;
    use TimestampableTrait;
    use ApprovableTrait;
    /** @use UpdateProposableTrait<JobUpdateProposalModel> */
    use UpdateProposableTrait;

    /**
     * The job's slug name.
     */
    #[Column(type: 'string')]
    protected string $slugName;

    /**
     * The job's status.
     */
    #[Column(type: 'boolean')]
    protected bool $published;

    /**
     * The job's contact's name.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $contactName;

    /**
     * The job's phone.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $contactPhone;

    /**
     * The job's email.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $contactEmail;

    /**
     * The job's display name.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'name_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected CompanyLocalisedText $name;

    /**
     * The job's location.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'location_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected CompanyLocalisedText $location;

    /**
     * The job's website.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'website_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected CompanyLocalisedText $website;

    /**
     * The job's description.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'description_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected CompanyLocalisedText $description;

    /**
     * The location(url) of an attachment describing the job.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'attachment_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected CompanyLocalisedText $attachment;

    /**
     * The job's package.
     */
    #[ManyToOne(
        targetEntity: CompanyJobPackage::class,
        inversedBy: 'jobs',
    )]
    #[JoinColumn(
        name: 'package_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected CompanyJobPackage $package;

    /**
     * The job's category.
     */
    #[ManyToOne(targetEntity: JobCategory::class)]
    #[JoinColumn(
        name: 'category_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected JobCategory $category;

    /**
     * Job labels.
     *
     * @var Collection<array-key, JobLabel>
     */
    #[ManyToMany(
        targetEntity: JobLabel::class,
        inversedBy: 'jobs',
        cascade: ['persist'],
    )]
    #[JoinTable(name: 'JobLabelAssignment')]
    protected Collection $labels;

    /**
     * Proposed updates to this job.
     *
     * @var Collection<array-key, JobUpdateProposalModel>
     */
    #[OneToMany(
        targetEntity: JobUpdateProposalModel::class,
        mappedBy: 'original',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
        fetch: 'EXTRA_LAZY',
    )]
    protected Collection $updateProposals;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->updateProposals = new ArrayCollection();
    }

    /**
     * Get the job's name.
     */
    public function getName(): CompanyLocalisedText
    {
        return $this->name;
    }

    /**
     * Set the job's name.
     */
    public function setName(CompanyLocalisedText $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the job's category.
     */
    public function getCategory(): JobCategory
    {
        return $this->category;
    }

    /**
     * Set the job's category.
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
     */
    public function setSlugName(string $name): void
    {
        $this->slugName = $name;
    }

    /**
     * Get the job's status.
     */
    public function isPublished(): bool
    {
        return $this->published;
    }

    public function isActive(): bool
    {
        return $this->isApproved()
            && $this->isPublished()
            && !$this->isUpdate()
            && $this->getPackage()->isActive()
            && !$this->getPackage()->getCompany()->isHidden();
    }

    /**
     * Set the job's status.
     */
    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    /**
     * Get the job's website.
     */
    public function getWebsite(): CompanyLocalisedText
    {
        return $this->website;
    }

    /**
     * Set the job's website.
     */
    public function setWebsite(CompanyLocalisedText $website): void
    {
        $this->website = $website;
    }

    /**
     * Get the job's attachment.
     */
    public function getAttachment(): CompanyLocalisedText
    {
        return $this->attachment;
    }

    /**
     * Set the job's attachment.
     */
    public function setAttachment(CompanyLocalisedText $attachment): void
    {
        $this->attachment = $attachment;
    }

    /**
     * Get the job's contact's name.
     */
    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    /**
     * Set the job's contact's name.
     */
    public function setContactName(?string $name): void
    {
        $this->contactName = $name;
    }

    /**
     * Get the job's contact's phone.
     */
    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    /**
     * Set the job's contact's phone.
     */
    public function setContactPhone(?string $contactPhone): void
    {
        $this->contactPhone = $contactPhone;
    }

    /**
     * Get the job's contact's email.
     */
    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    /**
     * Set the job's contact's email.
     */
    public function setContactEmail(?string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    /**
     * Get the job's description.
     */
    public function getDescription(): CompanyLocalisedText
    {
        return $this->description;
    }

    /**
     * Set the job's description.
     */
    public function setDescription(CompanyLocalisedText $description): void
    {
        $this->description = $description;
    }

    /**
     * Get the job's package.
     */
    public function getPackage(): CompanyJobPackage
    {
        return $this->package;
    }

    /**
     * Get the job's company.
     */
    public function getCompany(): Company
    {
        return $this->getPackage()->getCompany();
    }

    /**
     * Get the labels. Returns an array of JobLabelAssignments.
     *
     * @return Collection<array-key, JobLabel>
     */
    public function getLabels(): Collection
    {
        return $this->labels;
    }

    /**
     * @param JobLabel[] $labels
     */
    public function addLabels(array $labels): void
    {
        foreach ($labels as $label) {
            $this->addLabel($label);
        }
    }

    public function addLabel(JobLabel $label): void
    {
        if ($this->labels->contains($label)) {
            return;
        }

        $this->labels->add($label);
        $label->addJob($this);
    }

    /**
     * @param JobLabel[] $labels
     */
    public function removeLabels(array $labels): void
    {
        foreach ($labels as $label) {
            $this->removeLabel($label);
        }
    }

    public function removeLabel(JobLabel $label): void
    {
        if (!$this->labels->contains($label)) {
            return;
        }

        $this->labels->removeElement($label);
        $label->removeJob($this);
    }

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
     */
    public function getLocation(): CompanyLocalisedText
    {
        return $this->location;
    }

    /**
     * Sets the job's location.
     */
    public function setLocation(CompanyLocalisedText $location): void
    {
        $this->location = $location;
    }

    /**
     * @return Collection<array-key, JobUpdateProposalModel>
     */
    public function getUpdateProposals(): Collection
    {
        return $this->updateProposals;
    }

    /**
     * @return array{
     *     slugName: string,
     *     category: JobCategory,
     *     contactName: ?string,
     *     contactEmail: ?string,
     *     contactPhone: ?string,
     *     published: bool,
     *     name: ?string,
     *     nameEn: ?string,
     *     location: ?string,
     *     locationEn: ?string,
     *     website: ?string,
     *     websiteEn: ?string,
     *     description: ?string,
     *     descriptionEn: ?string,
     *     attachment: ?string,
     *     attachmentEn: ?string,
     *     labels: ImportedJobLabelArrayType[],
     * }
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

    public function getResourceId(): string
    {
        return 'job';
    }
}
