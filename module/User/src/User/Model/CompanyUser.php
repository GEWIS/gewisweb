<?php


namespace User\Model;

use Company\Model\Company;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

/**
 * CompanyUser model.
 *
 * Create the table in the database
 * @ORM\Table(name="CompanyUser")
 * @ORM\Entity
 */

class CompanyUser extends Model implements RoleInterface, ResourceInterface
{
    /**
     * The company ID.
     *
     * Create column of right type in database
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * The company's contact email address.
     *
     * Create column of right type in database
     * @ORM\Column(type="string")
     */
    protected $contactEmail;

    /**
     * The company's password.
     *
     * Create column of right type in database
     * @ORM\Column(type="string")
     */
    protected $password;

    /**
     * Companies sessions
     *
     * @ORM\OneToMany(targetEntity="User\Model\Session", mappedBy="company")
     */
    protected $sessions;

    /**
     * Constructor
     *
     * Construct a NewCompany to set in the database
     */
    public function __construct(NewCompany $newCompany = null)
    {
        if (null !== $newCompany) {
            $this->id = $newCompany->getId();
            $this->companyAccount = $newCompany->getCompany();
            $this->contactEmail = $newCompany->getContactEmail();
        }
    }

    /**
     * The corresponding companyAccount for this company.
     *
     * Database tables Company and CompanyUser are joined on company ID
     * @ORM\OneToOne(targetEntity="Company\Model\Company", fetch="EAGER")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     */
    protected $companyAccount;


    /**
     * Get the company ID.
     *
     * @return int
     */
    public function getLidnr()
    {
        return $this->id;
    }

    /**
     * Get the id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the company's contact email address.
     *
     * @return string
     */
    public function getContactEmail()
    {
        return $this->contactEmail;
    }

    /**
     * Get the password hash.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the password hash.
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param string $contactEmail
     */
    public function setContactEmail($contactEmail)
    {
        $this->contactEmail = $contactEmail;
    }

    /**
     * @param mixed $sessions
     */
    public function setSessions($sessions)
    {
        $this->sessions = $sessions;
    }

    /**
     * Get the corresponding companyAccount for this company.
     *
     * @return CompanyUser
     */
    public function getCompanyAccount()
    {
        return $this->companyAccount;
    }

    /**
     * Set the corresponding companyAccount for this company.
     *
     * @param CompanyUser $companyAccount
     */
    public function setCompanyAccount($companyAccount)
    {
        $this->companyAccount = $companyAccount;
    }

    /**
     * Get the company's role ID.
     *
     * @return string
     */
    public function getRoleId()
    {
        return 'company_user_' . $this->getLidnr();
    }

    /**
     * Get the company role name.
     *
     * @return array Role names
     */
    public function getRoleNames()
    {
        return ["company_user"];
    }

    /**
     * Get the company's resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'companyUser';
    }

    /**
     * Updates this object with values in the form of getArrayCopy()
     *
     */
    public function exchangeArray($data)
    {
        $this->setContactEmail($this->updateIfSet($data['contactEmail'],''));
    }
}
