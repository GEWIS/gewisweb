<?php

namespace User\Model;

use DateTime;
use Company\Model\Company;
use User\Service\User as UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * User model.
 *
 * @ORM\Entity
 */
class NewCompany
{
    /**
     * The company ID.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * The company's contact email address.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $contactEmail;

    /**
     * The company's activation code.
     *
     * @ORM\Column(type="string")
     */
    protected $code;

    /**
     * Registration attempt timestamp
     *
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $time;

    /**
     * User's company
     *
     * @ORM\OneToOne(targetEntity="Company\Model\Company")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     */
    protected $company;

    /**
     * Constructor.
     *
     * We can populate most values from a company model.
     *
     * @param Company $company
     */
    public function __construct(Company $company = null)
    {
        if (null !== $company) {
            $this->company = $company;
            $this->code = $this->generateCode();
        }
    }

    /**
     * Generate an activation code for the user.
     *
     * @param int $length
     *
     * @return string
     */
    public function generateCode($length = 20)
    {
        $ret = '';
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        for ($i = 0; $i < $length; $i++) {
            $ret .= $alphabet[rand(0, strlen($alphabet) - 1)];
        }

        return $ret;
    }

    /**
     * Get the company id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setCode($code)
    {
        $this->code = $code;
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
     * Get the activation code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get the registration time.
     *
     * @return DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Get the company.
     *
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set the company ID
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set the company's contact email address
     * @param mixed $contactEmail
     */
    public function setContactEmail($contactEmail)
    {
        $this->contactEmail = $contactEmail;
    }

    /**
     * Updates this object with values in the form of getArrayCopy()
     *
     */
    public function exchangeArray($data)
    {
        $this->setContactEmail($this->updateIfSet($data['contactEmail'],''));
    }

    /**
     * Updates the variable if the first argument is set, Otherwise, it will
     * use the second argument.
     *
     * @param mixed $object
     * @param mixed $default
     */
    private function updateIfSet($object, $default)
    {
        if (isset($object)) {
            return $object;
        }

        return $default;
    }
}
