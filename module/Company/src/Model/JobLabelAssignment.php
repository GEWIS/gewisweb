<?php

namespace Company\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
};

/**
 * Job Label Assignment model.
 * Used for mapping labels to jobs.
 */
#[Entity]
class JobLabelAssignment
{
    /**
     * The label id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     *
     */
    #[ManyToOne(
        targetEntity: Job::class,
        inversedBy: "labels",
    )]
    #[JoinColumn(
        name: "job_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected Job $job;

    /**
     *
     */
    #[ManyToOne(
        targetEntity: JobLabel::class,
        inversedBy: "assignments",
        fetch: "EAGER",
    )]
    #[JoinColumn(
        name: "label_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected JobLabel $label;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Job
     */
    public function getJob(): Job
    {
        return $this->job;
    }

    /**
     * @param Job $job
     */
    public function setJob(Job $job): void
    {
        $this->job = $job;
    }

    /**
     * @return JobLabel
     */
    public function getLabel(): JobLabel
    {
        return $this->label;
    }

    /**
     * @param JobLabel $label
     */
    public function setLabel(JobLabel $label): void
    {
        $this->label = $label;
    }
}
