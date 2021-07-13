<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Signup model.
 *
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"user"="UserSignup","external"="ExternalSignup"})
 */
abstract class Signup
{
    /**
     * ID for the signup.
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * The SignupList the signup is for.
     *
     * @ORM\ManyToOne(targetEntity="Activity\Model\SignupList", inversedBy="signUps")
     * @ORM\JoinColumn(name="signuplist_id", referencedColumnName="id")
     */
    protected $signupList;

    /**
     * Additional field values for this Signup.
     *
     * @ORM\OneToMany(targetEntity="Activity\Model\SignupFieldValue", mappedBy="signup", cascade={"persist", "remove"})
     */
    protected $fieldValues;

    /**
     * Get the signup id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the SignupList which the user is signed up for.
     *
     * @return SignupList
     */
    public function getSignupList()
    {
        return $this->signupList;
    }

    /**
     * Set the SignupList that the user signed up for.
     */
    public function setSignupList(SignupList $signupList)
    {
        $this->signupList = $signupList;
    }

    /**
     * Get all the extra field values.
     *
     * @return array
     */
    public function getFieldValues()
    {
        return $this->fieldValues;
    }

    /**
     * Get the full name of the user whom signed up for the SignupList.
     *
     * @return string
     */
    abstract public function getFullName();

    /**
     * Get the email address of the user whom signed up for the SignupList.
     *
     * @return string
     */
    abstract public function getEmail();
}
