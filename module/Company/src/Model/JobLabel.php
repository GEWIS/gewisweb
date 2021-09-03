<?php

namespace Company\Model;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    ManyToMany,
    OneToOne,
};

/**
 * Job Label model.
 */
#[Entity]
class JobLabel
{
    /**
     * The label id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * The name of the label.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    protected CompanyLocalisedText $name;

    /**
     * The abbreviation of the label.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    protected CompanyLocalisedText $abbreviation;

    /**
     * The Assignments this Label belongs to.
     */
    #[ManyToMany(
        targetEntity: Job::class,
        mappedBy: "labels",
        cascade: ["persist"],
    )]
    protected Collection $jobs;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->jobs = new ArrayCollection();
    }

    /**
     * Gets the id.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Sets the id.
     *
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Gets the name.
     *
     * @return CompanyLocalisedText
     */
    public function getName(): CompanyLocalisedText
    {
        return $this->name;
    }

    /**
     * Sets the name.
     *
     * @param CompanyLocalisedText $name
     */
    public function setName(CompanyLocalisedText $name): void
    {
        $this->name = $name;
    }

    /**
     * Gets the slug.
     *
     * @return CompanyLocalisedText
     */
    public function getAbbreviation(): CompanyLocalisedText
    {
        return $this->abbreviation;
    }

    /**
     * Sets the slug.
     *
     * @param CompanyLocalisedText $slug
     */
    public function setAbbreviation(CompanyLocalisedText $slug): void
    {
        $this->abbreviation = $slug;
    }

    /**
     * Gets the jobs associated with this label.
     *
     * @return Collection
     */
    public function getJobs(): Collection
    {
        return $this->jobs;
    }

    /**
     * @param Job $job
     */
    public function addJob(Job $job): void
    {
        if ($this->jobs->contains($job)) {
            return;
        }

        $this->jobs->add($job);
    }

    /**
     * @param Job $job
     */
    public function removeJob(Job $job): void
    {
        if (!$this->jobs->contains($job)) {
            return;
        }

        $this->jobs->removeElement($job);
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
            'abbreviation' => $this->getAbbreviation()->getValueNL(),
            'abbreviationEn' => $this->getAbbreviation()->getValueEN(),
        ];
    }
}
