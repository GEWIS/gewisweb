<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Job Label Assignment model.
 * Used for mapping labels to jobs
 *
 * @ORM\Entity
 */
class JobLabelAssignment
{
    /**
     * The label id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;


    /**
     * @ORM\ManyToOne(targetEntity="Company\Model\Job", inversedBy="labels")
     * @ORM\JoinColumn(name="job_id",referencedColumnName="id")
     */
    protected $job;

    /**
     * @ORM\ManyToOne(targetEntity="Company\Model\JobLabel")
     * @ORM\JoinColumn(name="label_id",referencedColumnName="id")
     */
    protected $label;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param Job $job
     */
    public function setJob($job)
    {
        $this->job = $job;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }
}
