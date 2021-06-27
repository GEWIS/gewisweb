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
     * The approvals approved status
     *
     * @ORM\Column(type="boolean")
     */
    protected $rejected = false;


    /**
     * Set pending approval to rejected status
     *
     * @param bool $rejected
     */
    public function setRejected($rejected)
    {
        $this->rejected = $rejected;
    }

    /**
     * The vacancy approval model
     *
     * @ORM\OneToOne(targetEntity="\Company\Model\ApprovalModel\ApprovalVacancy")
     * @var ApprovalAbstract
     */
    protected $VacancyApproval;

    /**
     * The banner approval model
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\CompanyPackage")
     * @var ApprovalAbstract
     */
    protected $BannerApproval;

    /**
     * The profile approval model
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\ApprovalModel\ApprovalProfile")
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
     * Get the approval's status
     *
     * @return bool
     */
    public function getRejected()
    {
        return $this->rejected;
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


    /**
     * @param ApprovalAbstract $VacancyApproval
     */
    public function setVacancyApproval($VacancyApproval)
    {
        $this->VacancyApproval = $VacancyApproval;
    }

    /**
     * @param ApprovalAbstract $BannerApproval
     */
    public function setBannerApproval($BannerApproval)
    {
        $this->BannerApproval = $BannerApproval;
    }

    /**
     * @param ApprovalAbstract $ProfileApproval
     */
    public function setProfileApproval($ProfileApproval)
    {
        $this->ProfileApproval = $ProfileApproval;
    }

    /**
     * @param String $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @param String $type
     */
    public function setBaner($banner)
    {
        $this->BannerApproval = $banner;
    }

    /**
     * Get the approval's company
     *
     * @return Company
     */
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

    /**
     * Get the approval's status
     *
     * @return bool
     */
    public function getStatus(){
        if(!is_null($this->VacancyApproval)){
            return $this->VacancyApproval->getRejected();

        }else if(!is_null($this->BannerApproval)){
            return $this->getRejected();

        }else if(!is_null($this->ProfileApproval)){

            return $this->ProfileApproval->getRejected();

        }else{
            return Null;
        }
    }

}
