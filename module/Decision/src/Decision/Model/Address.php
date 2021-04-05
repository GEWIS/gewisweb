<?php

namespace Decision\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Address model.
 *
 * @ORM\Entity
 */
class Address
{
    const TYPE_HOME = 'home';
    const TYPE_STUDENT = 'student'; // student room
    const TYPE_MAIL = 'mail'; // mailing address

    /**
     * Member.
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Member", inversedBy="addresses"))
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $member;

    /**
     * Type
     *
     * Can be one of:
     *
     * - home (Parent's home)
     * - student (Student's home)
     * - mail (Where GEWIS mail should go to)
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * Country.
     *
     * By default, netherlands.
     *
     * @ORM\Column(type="string")
     */
    protected $country = 'netherlands';

    /**
     * Street.
     *
     * @ORM\Column(type="string")
     */
    protected $street;

    /**
     * House number (+ suffix)
     *
     * @ORM\Column(type="string")
     */
    protected $number;

    /**
     * Postal code.
     *
     * @ORM\Column(type="string")
     */
    protected $postalCode;

    /**
     * City.
     *
     * @ORM\Column(type="string")
     */
    protected $city;

    /**
     * Phone number.
     *
     * @ORM\Column(type="string")
     */
    protected $phone;

    /**
     * Get available address types.
     *
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::TYPE_HOME,
            self::TYPE_STUDENT,
            self::TYPE_MAIL,
        ];
    }

    /**
     * Get the member.
     *
     * @return Member
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * Set the member.
     *
     * @param Member $member
     */
    public function setMember(Member $member)
    {
        $this->member = $member;
    }

    /**
     * Get the type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the type.
     *
     * @param string $type
     *
     * @throws \InvalidArgumentException When the type is incorrect
     */
    public function setType($type)
    {
        if (!in_array($type, self::getTypes())) {
            throw new \InvalidArgumentException("Non-existing type.");
        }
        $this->type = $type;
    }

    /**
     * Get the country.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set the country.
     *
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Get the street.
     *
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set the street.
     *
     * @param string $street
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }

    /**
     * Get the house number (+ suffix).
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set the house number (+ suffix).
     *
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * Set the postal code.
     *
     * @param string $postalCode
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
    }

    /**
     * Get the postal code.
     *
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Get the city.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set the city.
     *
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * Get the phone number.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set the phone number.
     *
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }
}
