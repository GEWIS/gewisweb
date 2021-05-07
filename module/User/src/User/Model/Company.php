<?php


namespace User\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

/**
 * Company model.
 * @Table(name=('CompanyUser'))
 * @ORM\Entity
 */

class Company extends Model implements RoleInterface, ResourceInterface
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
     * TODO: check if mappedby user is fine
     *
     * @ORM\OneToMany(targetEntity="User\Model\Session", mappedBy="user")
     */
    protected $sessions;

//    /**
//     * Constructor
//     */
//    public function __construct(NewCompany $newCompany = null)
//    {
//
//        if (null !== $newCompany) {
//            $this->id = $newCompany->getId();
//            $this->contactEmail = $newCompany->getContactEmail();
//        }
//    }


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
     * Get the company's role ID.
     *
     * @return string
     */
    public function getRoleId()
    {
        return 'company_' . $this->getLidnr();
    }

    /**
     * Get the company's resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'company';
    }


}
