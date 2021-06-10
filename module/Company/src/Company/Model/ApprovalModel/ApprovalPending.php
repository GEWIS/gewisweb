<?php


namespace Company\Model\ApprovalModel;
use Doctrine\ORM\Mapping as ORM;

/**
 * PendingApproval model.
 *
 * @ORM\Entity
 */
class ApprovalPending
{
    /**
     * The job id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * The approval
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\ApprovalModel\ApprovalVacancy", inversedBy="packages")
     * @var ApprovalAbstract
     */
    protected $VacancyApproval;

    /**
     * The approval
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\ApprovalModel\ApprovalVacancy", inversedBy="packages")
     * @var ApprovalAbstract
     */
    protected $BannerApproval;

    /**
     * The approval
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\ApprovalModel\ApprovalProfile", inversedBy="packages")
     * @var ApprovalAbstract
     */
    protected $ProfileApproval;

    /**
     * The pending approval's type.
     *
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * Get the pending approvals id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the vacancy approval.
     *
     * @return ApprovalAbstract
     */
    public function getVacancyApproval()
    {
        return $this->VacancyApproval;
    }

    /**
     * Get the banner approval.
     *
     * @return ApprovalAbstract
     */
    public function getBannerApproval()
    {
        return $this->BannerApproval;
    }

    /**
     * Get the profile approval.
     *
     * @return ApprovalAbstract
     */
    public function getProfileApproval()
    {
        return $this->ProfileApproval;
    }

    /**
     * Get the pending approval's type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function getCompany(){
        if(!is_null($this->VacancyApproval)){
            return $this->VacancyApproval->getCompany();

        }else if(!is_null($this->BannerApproval)){
            return $this->BannerApproval->getCompany();

        }else if(!is_null($this->ProfileApproval)){

            return $this->ProfileApproval->getCompany();

        }else{
            return Null;
        }
    }

    public function getStatus(){
        if(!is_null($this->VacancyApproval)){
            return $this->VacancyApproval->getRejected();

        }else if(!is_null($this->BannerApproval)){
            return $this->BannerApproval->getRejected();

        }else if(!is_null($this->ProfileApproval)){

            return $this->ProfileApproval->getRejected();

        }else{
            return Null;
        }
    }

}
