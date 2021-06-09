<?php


namespace Company\Model\ApprovalModel;
use Company\Model\ApprovalModel\ApprovalAbstract;
use Doctrine\ORM\Mapping as ORM;
use Company\Model\Company;

/**
 * VacancyApproval model.
 *
 *
 * @ORM\Entity
 *
 *
 */
class ApprovalProfile implements ApprovalAbstract
{



    /**
     * The profile approvals company
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\Company", inversedBy="translations", cascade={"persist"})
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
     * The company id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @ORM\OneToOne(targetEntity="User\Model\Company")
     */
    protected $id;

    /**
     * Translations of details of the company.
     * Are of type \Company\Model\CompanyI18n.
     *
     * @ORM\OneToMany(targetEntity="\Company\Model\CompanyI18n", mappedBy="company", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $translations;

    /**
     * The company's display name.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * The company's slug version of the name. (username).
     *
     * @ORM\Column(type="string")
     */
    protected $slugName;

    /**
     * The company's contact's name.
     *
     * @ORM\Column(type="string")
     */
    protected $contactName;

    /**
     * The company's address.
     *
     * @ORM\Column(type="string")
     */
    protected $address;

    /**
     * The company's contact email
     *
     * @ORM\Column(type="string")
     */
    protected $contactEmail;

    /**
     * The company's public email.
     *
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * The company's phone.
     *
     * @ORM\Column(type="string")
     */
    protected $phone;

    /**
     * Whether the company is hidden.
     *
     * @ORM\Column(type="boolean")
     */
    protected $hidden;

    /**
     * The company's packages.
     *
     * @ORM\OneToMany(targetEntity="\Company\Model\CompanyPackage", mappedBy="company", cascade={"persist", "remove"})
     */
    protected $packages;

    /**
     * The company's phone.
     *
     * @ORM\Column(type="integer")
     */
    protected $highlightCredits;

    /**
     * The company banner credits.
     *
     * @ORM\Column(type="integer")
     */
    protected $bannerCredits;

    /**
     * The job's category.
     *
     * @ORM\ManyToOne(targetEntity="\Company\Model\JobSector")
     */
    protected $sector;

//    /**
//     * The companies sector.
//     *
//     * @ORM\Column(type="string")
//     */
//    protected $sector;

    /**
     * The companies email subscription.
     *
     * @ORM\Column(type="boolean")
     */
    protected $emailSubscription;

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
