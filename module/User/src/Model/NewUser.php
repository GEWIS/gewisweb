<?php

namespace User\Model;

use DateTime;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    Id,
    JoinColumn,
    OneToOne,
};

/**
 * User model.
 */
#[Entity]
class NewUser
{
    /**
     * The membership number.
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $lidnr;

    /**
     * The user's email address.
     */
    #[Column(type: "string")]
    protected string $email;

    /**
     * The user's activation code.
     */
    #[Column(type: "string")]
    protected string $code;

    /**
     * User's member.
     */
    #[OneToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        name: "lidnr",
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected MemberModel $member;

    /**
     * Registration attempt timestamp.
     */
    #[Column(
        type: "datetime",
        nullable: true,
    )]
    protected ?DateTime $time = null;

    /**
     * Constructor.
     *
     * We can populate most values from a member model.
     *
     * @param MemberModel $member
     */
    public function __construct(MemberModel $member)
    {
        if (null !== $member) {
            $this->lidnr = $member->getLidnr();
            $this->email = $member->getEmail();
            $this->member = $member;
        }
    }

    /**
     * Get the membership number.
     *
     * @return int
     */
    public function getLidnr(): int
    {
        return $this->lidnr;
    }

    /**
     * Get the user's email address.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get the activation code.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get the registration time.
     *
     * @return DateTime|null
     */
    public function getTime(): ?DateTime
    {
        return $this->time;
    }

    /**
     * Get the member.
     *
     * @return MemberModel
     */
    public function getMember(): MemberModel
    {
        return $this->member;
    }

    /**
     * Set the user's membership number.
     *
     * @param int $lidnr
     */
    public function setLidnr(int $lidnr): void
    {
        $this->lidnr = $lidnr;
    }

    /**
     * Set the user's email address.
     *
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Set the activation code.
     *
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * Set the registration time.
     *
     * @param DateTime|null $time
     */
    public function setTime(?DateTime $time): void
    {
        $this->time = $time;
    }
}
