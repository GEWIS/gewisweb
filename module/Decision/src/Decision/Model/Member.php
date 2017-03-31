<?php

namespace Decision\Model;

use Decision\Model\SubDecision\Installation;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Member model.
 *
 * @ORM\Entity
 */
class Member
{

    const GENDER_MALE = 'm';
    const GENDER_FEMALE = 'f';
    const GENDER_OTHER = 'o';

    const TYPE_ORDINARY = 'ordinary';
    const TYPE_PROLONGED = 'prolonged';
    const TYPE_EXTERNAL = 'external';
    const TYPE_EXTRAORDINARY = 'extraordinary';
    const TYPE_HONORARY = 'honorary';

    /**
     * The user
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="lidnr")
     * @ORM\OneToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $lidnr;

    /**
     * Member's email address.
     *
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * Member's last name.
     *
     * @ORM\Column(type="string")
     */
    protected $lastName;

    /**
     * Middle name.
     *
     * @ORM\Column(type="string")
     */
    protected $middleName;

    /**
     * Initials.
     *
     * @ORM\Column(type="string")
     */
    protected $initials;

    /**
     * First name.
     *
     * @ORM\Column(type="string")
     */
    protected $firstName;

    /**
     * Gender of the member.
     *
     * Either one of:
     * - m
     * - f
     *
     * @ORM\Column(type="string",length=1)
     */
    protected $gender;

    /**
     * Generation.
     *
     * This is the year that this member became a GEWIS member. This is not
     * a academic year, but rather a calendar year.
     *
     * @ORM\Column(type="integer")
     */
    protected $generation;

    /**
     * Member type.
     *
     * This can be one of the following, as defined by the GEWIS statuten:
     *
     * - ordinary
     * - prolonged
     * - external
     * - extraordinary
     * - honorary
     *
     * You can find the GEWIS Statuten here:
     *
     * http://gewis.nl/vereniging/statuten/statuten.php
     *
     * Zie artikel 7 lid 1 en 2.
     *
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * Last changed date of membership.
     *
     * @ORM\Column(type="date")
     */
    protected $changedOn;

    /**
     * Member birth date.
     *
     * @ORM\Column(type="date")
     */
    protected $birth;

    /**
     * Member expiration date.
     *
     * @ORM\Column(type="date")
     */
    protected $expiration;

    /**
     * How much the member has paid for membership. 0 by default.
     *
     * @ORM\Column(type="integer")
     */
    protected $paid = 0;

    /**
     * Iban number.
     *
     * @ORM\Column(type="string",nullable=true)
     */
    protected $iban;
    /**
     * If the member receives a 'supremum'
     *
     * @ORM\Column(type="string",nullable=true)
     */
    protected $supremum;

    /**
     * Addresses of this member.
     *
     * @ORM\OneToMany(targetEntity="Address", mappedBy="member",cascade={"persist"})
     */
    protected $addresses;

    /**
     * Installations of this member.
     *
     * @ORM\OneToMany(targetEntity="Decision\Model\SubDecision\Installation",mappedBy="member")
     */
    protected $installations;

    /**
     * Memberships of mailing lists.
     *
     * @ORM\ManyToMany(targetEntity="MailingList", inversedBy="members")
     * @ORM\JoinTable(name="members_mailinglists",
     *      joinColumns={@ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="name", referencedColumnName="name")}
     * )
     */
    protected $lists;

    /**
     * Organ memberships.
     *
     * @ORM\OneToMany(targetEntity="OrganMember", mappedBy="member")
     */
     protected $organInstallations;

    /**
     * Board memberships.
     *
     * @ORM\OneToMany(targetEntity="BoardMember", mappedBy="member")
     */
     protected $boardInstallations;

    /**
     * Static method to get available genders.
     *
     * @return array
     */
    protected static function getGenders()
    {
        return [
            self::GENDER_MALE,
            self::GENDER_FEMALE,
            self::GENDER_OTHER
        ];
    }

    /**
     * Static method to get available member types.
     *
     * @return array
     */
    protected static function getTypes()
    {
        return [
            self::TYPE_ORDINARY,
            self::TYPE_PROLONGED,
            self::TYPE_EXTERNAL,
            self::TYPE_EXTRAORDINARY,
            self::TYPE_HONORARY
        ];
    }


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        $this->installations = new ArrayCollection();
        $this->organInstallations = new ArrayCollection();
        $this->boardInstallations = new ArrayCollection();
        $this->lists = new ArrayCollection();
    }

    /**
     * Get the membership number.
     *
     * @return int
     */
    public function getLidnr()
    {
        return $this->lidnr;
    }

    /**
     * Get the member's email address.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get the member's last name.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Get the member's middle name.
     *
     * @return string
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * Get the member's initials.
     *
     * @return string
     */
    public function getInitials()
    {
        return $this->initials;
    }

    /**
     * Get the member's first name.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set the lidnr.
     *
     * @param string $lidnr
     */
    public function setLidnr($lidnr)
    {
        $this->lidnr = $lidnr;
    }

    /**
     * Set the member's email address.
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Set the member's last name.
     *
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * Set the member's middle name.
     *
     * @param string $middleName
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;
    }

    /**
     * Set the member's initials.
     *
     * @param string $initals
     */
    public function setInitials($initials)
    {
        $this->initials = $initials;
    }

    /**
     * Set the member's first name.
     *
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * Assemble the member's full name.
     *
     * @return string
     */
    public function getFullName()
    {
        $name = $this->getFirstName() . ' ';

        $middle = $this->getMiddleName();
        if (!empty($middle)) {
            $name .= $middle . ' ';
        }

        return $name . $this->getLastName();
    }

    /**
     * Get the member's gender.
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set the member's gender.
     *
     * @param string $gender
     *
     * @throws \InvalidArgumentException when the gender does not have correct value
     */
    public function setGender($gender)
    {
        if (!in_array($gender, self::getGenders())) {
            throw new \InvalidArgumentException("Invalid gender value");
        }
        $this->gender = $gender;
    }

    /**
     * Get the generation.
     *
     * @return string
     */
    public function getGeneration()
    {
        return $this->generation;
    }

    /**
     * Set the generation.
     *
     * @param string $generation
     */
    public function setGeneration($generation)
    {
        $this->generation = $generation;
    }

    /**
     * Get the member type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the member type.
     *
     * @param string $type
     *
     * @throws \InvalidArgumentException When the type is incorrect.
     */
    public function setType($type)
    {
        if (!in_array($type, self::getTypes())) {
            throw new \InvalidArgumentException("Nonexisting type given.");
        }
        $this->type = $type;
    }

    /**
     * Get the expiration date.
     *
     * The information comes from the statuten and HR.
     *
     * @return \DateTime
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * Set the expiration date.
     *
     * @param \DateTime $expiration
     */
    public function setExpiration($expiration)
    {
        $this->expiration = $expiration;
    }

    /**
     * Get the birth date.
     *
     * @return \DateTime
     */
    public function getBirth()
    {
        return $this->birth;
    }

    /**
     * Set the birthdate.
     *
     * @param \DateTime $birth
     */
    public function setBirth(\DateTime $birth)
    {
        $this->birth = $birth;
    }

    /**
     * Get the date of the last membership change.
     *
     * @return \DateTime
     */
    public function getChangedOn()
    {
        return $this->changedOn;
    }

    /**
     * Set the date of the last membership change.
     *
     * @param \DateTime $changedOn
     */
    public function setChangedOn($changedOn)
    {
        $this->changedOn = $changedOn;
    }

    /**
     * Get how much has been paid.
     *
     * @return int
     */
    public function getPaid()
    {
        return $this->paid;
    }

    /**
     * Set how much has been paid.
     *
     * @param int $paid
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;
    }

    /**
     * Get the installations.
     *
     * @return ArrayCollection
     */
    public function getInstallations()
    {
        return $this->installations;
    }

    /**
     * Get the organ installations.
     *
     * @return ArrayCollection
     */
    public function getOrganInstallations()
    {
        return $this->organInstallations;
    }

    /**
     * Get the organ installations of organs that the member is currently part of
     *
     * @return ArrayCollection
     */
    public function getCurrentOrganInstallations()
    {
        if (is_null($this->getOrganInstallations())) {
            return new ArrayCollection();
        }

        return $this->getOrganInstallations()->filter(function (OrganMember $organ) {
            return is_null($organ->getDischargeDate());
        });
    }

    /**
     * Get the board installations.
     *
     * @return ArrayCollection
     */
    public function getBoardInstallations()
    {
        return $this->boardInstallations;
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'lidnr' => $this->getLidnr(),
            'email' => $this->getEmail(),
            'fullName' => $this->getFullName(),
            'lastName' => $this->getLastName(),
            'middleName' => $this->getMiddleName(),
            'initials' => $this->getInitials(),
            'firstName' => $this->getFirstName(),
            'generation' => $this->getGeneration(),
            'expiration' => $this->getExpiration()->format('l j F Y')
        ];
    }

    public function toApiArray()
    {
        return [
            'lidnr' => $this->getLidnr(),
            'email' => $this->getEmail(),
            'fullName' => $this->getFullName(),
            'initials' => $this->getInitials(),
            'firstName' => $this->getFirstName(),
            'middleName' => $this->getMiddleName(),
            'lastName' => $this->getLastName(),
            'birth' => $this->getBirth()->format(\DateTime::ISO8601),
            'generation' => $this->getGeneration(),
            'expiration' => $this->getExpiration()->format(\DateTime::ISO8601),
        ];
    }

    /**
     * Get all addresses.
     *
     * @return ArrayCollection all addresses
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * Clear all addresses.
     */
    public function clearAddresses()
    {
        $this->addresses = new ArrayCollection();
    }

    /**
     * Add multiple addresses.
     *
     * @param array $addresses
     */
    public function addAddresses($addresses) {
        foreach ($addresses as $address) {
            $this->addAddress($address);
        }
    }

    /**
     * Add an address.
     *
     * @param Address $address
     */
    public function addAddress(Address $address)
    {
        $address->setMember($this);
        $this->addresses[] = $address;
    }

    /**
     * Get mailing list subscriptions.
     *
     * @return ArrayCollection
     */
    public function getLists()
    {
        return $this->lists;
    }

    /**
     * Add a mailing list subscription.
     *
     * Note that this is the owning side.
     *
     * @param MailingList $list
     */
    public function addList(MailingList $list)
    {
        $list->addMember($this);
        $this->lists[] = $list;
    }

    /**
     * Add multiple mailing lists.
     *
     * @param array $lists
     */
    public function addLists($lists)
    {
        foreach ($lists as $list) {
            $this->addList($list);
        }
    }

    /**
     * Clear the lists.
     */
    public function clearLists()
    {
        $this->lists = new ArrayCollection();
    }
}
