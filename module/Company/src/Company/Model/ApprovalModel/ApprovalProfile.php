<?php


namespace Company\Model\ApprovalModel;
use Company\Model\ApprovalModel\ApprovalAbstract;
use Doctrine\ORM\Mapping as ORM;

/**
 * VacancyApproval model.
 *
 * @ORM\Entity
 */
class ApprovalProfile implements ApprovalAbstract
{

    /**
     * The Profile approvals id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * The profile approvals company
     *
     * @ORM\Column(type="string")
     */
    protected $company;

    /**
     * The profile approvals approved status
     *
     * @ORM\Column(type="boolean")
     */
    protected $approved;

    // TODO add other profile variables

    /**
     * Get the approval's id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->hidden;
    }

    /**
     * Get the approval's company.
     *
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

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
