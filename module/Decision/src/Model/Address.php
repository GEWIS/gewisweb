<?php

namespace Decision\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    Id,
    JoinColumn,
    ManyToOne,
};
use InvalidArgumentException;

/**
 * Address model.
 */
#[Entity]
class Address
{
    public const TYPE_HOME = 'home';
    public const TYPE_STUDENT = 'student'; // student room
    public const TYPE_MAIL = 'mail'; // mailing address

    /**
     * Member.
     */
    #[Id]
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: "addresses",
    )]
    #[JoinColumn(
        name: "lidnr",
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected Member $member;

    /**
     * Type.
     *
     * Can be one of:
     *
     * - home (Parent's home)
     * - student (Student's home)
     * - mail (Where GEWIS mail should go to)
     */
    #[Id]
    #[Column(type: "string")]
    protected string $type;

    /**
     * Country.
     *
     * By default, netherlands.
     */
    #[Column(type: "string")]
    protected string $country = 'netherlands';

    /**
     * Street.
     */
    #[Column(type: "string")]
    protected string $street;

    /**
     * House number (+ suffix).
     */
    #[Column(type: "string")]
    protected string $number;

    /**
     * Postal code.
     */
    #[Column(type: "string")]
    protected string $postalCode;

    /**
     * City.
     */
    #[Column(type: "string")]
    protected string $city;

    /**
     * Phone number.
     */
    #[Column(type: "string")]
    protected string $phone;

    /**
     * Get available address types.
     *
     * @return array
     */
    public static function getTypes(): array
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
    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * Set the member.
     *
     * @param Member $member
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
    }

    /**
     * Get the type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the type.
     *
     * @param string $type
     *
     * @throws InvalidArgumentException When the type is incorrect
     */
    public function setType(string $type): void
    {
        if (!in_array($type, self::getTypes())) {
            throw new InvalidArgumentException('Non-existing type.');
        }
        $this->type = $type;
    }

    /**
     * Get the country.
     *
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Set the country.
     *
     * @param string $country
     */
    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    /**
     * Get the street.
     *
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * Set the street.
     *
     * @param string $street
     */
    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    /**
     * Get the house number (+ suffix).
     *
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * Set the house number (+ suffix).
     *
     * @param string $number
     */
    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    /**
     * Set the postal code.
     *
     * @param string $postalCode
     */
    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    /**
     * Get the postal code.
     *
     * @return string
     */
    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    /**
     * Get the city.
     *
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * Set the city.
     *
     * @param string $city
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    /**
     * Get the phone number.
     *
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * Set the phone number.
     *
     * @param string $phone
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }
}
