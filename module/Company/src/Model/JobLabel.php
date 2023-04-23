<?php

declare(strict_types=1);

namespace Company\Model;

use Application\Model\Traits\IdentifiableTrait;
use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Entity,
    JoinColumn,
    ManyToMany,
    OneToOne,
};

/**
 * Job Label model.
 */
#[Entity]
class JobLabel
{
    use IdentifiableTrait;

    /**
     * The name of the label.
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
     * The abbreviation of the label.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: "abbreviation_id",
        referencedColumnName: "id",
        nullable: false,
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

    public function __construct()
    {
        $this->jobs = new ArrayCollection();
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

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
            'abbreviation' => $this->getAbbreviation()->getValueNL(),
            'abbreviationEn' => $this->getAbbreviation()->getValueEN(),
        ];
    }
}
