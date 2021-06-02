<?php


namespace Company\Model\ApprovalModel;
use Company\Model\ApprovalModel\ApprovalAbstract;
use Doctrine\ORM\Mapping as ORM;
use Company\Model\Job;

/**
 * VacancyApproval modsel.
 *
 * @ORM\Entity
 */
class ApprovalVacancy extends Job implements ApprovalAbstract
{

    /**
     * The approval's status.
     *
     * @ORM\Column(type="boolean")
     */
    protected $approved = false;

    /**
     * Get the approval's approval status.
     *
     * @return boolean
     */
    public function getApproved()
    {
        return $this->approved;
    }
}
