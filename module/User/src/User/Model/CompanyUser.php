<?php


namespace User\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

/**
 * Company model.
 *
 * @ORM\Table(name="CompanyUser")
 * @ORM\Entity
 */

class CompanyUser extends Model implements RoleInterface, ResourceInterface
{
    /**
     * The membership number.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * The company's contactEmail address.
     * @ORM\Column(type="string")
     */
    protected $contactEmail;

    /**
     * The company's password.
     *
     * @ORM\Column(type="string")
     */
    protected $password;

    /**
     * Companies sessions
     * TODO: check if mappedby companyUser is fine
     *
     * @ORM\OneToMany(targetEntity="User\Model\Session", mappedBy="CompanyUser")
     */
    protected $sessions;

    /**
     * Constructor
     */
    // TODO: comments
    public function __construct(NewCompany $newCompany = null)
    {

        if (null !== $newCompany) {
            $this->id = $newCompany->getId();
            $this->contactEmail = $newCompany->getContactEmail();
        }
    }

    /**
     * The corresponding member for this user.
     *
     * @ORM\OneToOne(targetEntity="Company\Model\Company", fetch="EAGER")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     */
    protected $companyAccount;


    /**
     * Get the membership number.
     *
     * @return int
     */
    public function getLidnr()
    {
        return $this->id;
    }

    /**
     * Get the company's contactEmailaddress.
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
     * @return CompanyUser
     */
    public function getCompanyAccount()
    {
        return $this->companyAccount;
    }

    /**
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
}
