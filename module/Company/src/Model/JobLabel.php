<?php

declare(strict_types=1);

namespace Company\Model;

use Application\Model\Traits\IdentifiableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Job Label model.
 *
 * @psalm-type JobLabelArrayType = array{
 *     id: int,
 *     name: ?string,
 *     nameEn: ?string,
 *     abbreviation: ?string,
 *     abbreviationEn: ?string,
 * }
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
     * The abbreviation of the label.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'abbreviation_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected CompanyLocalisedText $abbreviation;

    /**
     * The Assignments this Label belongs to.
     *
     * @var Collection<array-key, Job>
     */
    #[ManyToMany(
        targetEntity: Job::class,
        mappedBy: 'labels',
        cascade: ['persist'],
    )]
    protected Collection $jobs;

    public function __construct()
    {
        $this->jobs = new ArrayCollection();
    }

    /**
     * Gets the name.
     */
    public function getName(): CompanyLocalisedText
    {
        return $this->name;
    }

    /**
     * Sets the name.
     */
    public function setName(CompanyLocalisedText $name): void
    {
        $this->name = $name;
    }

    /**
     * Gets the slug.
     */
    public function getAbbreviation(): CompanyLocalisedText
    {
        return $this->abbreviation;
    }

    /**
     * Sets the slug.
     */
    public function setAbbreviation(CompanyLocalisedText $slug): void
    {
        $this->abbreviation = $slug;
    }

    /**
     * Gets the jobs associated with this label.
     *
     * @return Collection<array-key, Job>
     */
    public function getJobs(): Collection
    {
        return $this->jobs;
    }

    public function addJob(Job $job): void
    {
        if ($this->jobs->contains($job)) {
            return;
        }

        $this->jobs->add($job);
    }

    public function removeJob(Job $job): void
    {
        if (!$this->jobs->contains($job)) {
            return;
        }

        $this->jobs->removeElement($job);
    }

    /**
     * @return JobLabelArrayType
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
