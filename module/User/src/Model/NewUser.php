<?php

declare(strict_types=1);

namespace User\Model;

use DateTime;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

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
    #[Column(type: 'integer')]
    protected int $lidnr;

    /**
     * The user's activation code.
     */
    #[Column(type: 'string')]
    protected string $code;

    /**
     * User's member.
     */
    #[OneToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    protected MemberModel $member;

    /**
     * Registration attempt timestamp.
     */
    #[Column(
        type: 'datetime',
        nullable: true,
    )]
    protected ?DateTime $time = null;

    public function __construct(MemberModel $member)
    {
        $this->lidnr = $member->getLidnr();
        $this->member = $member;
    }

    /**
     * Get the membership number.
     */
    public function getLidnr(): int
    {
        return $this->lidnr;
    }

    /**
     * Get the activation code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get the registration time.
     */
    public function getTime(): ?DateTime
    {
        return $this->time;
    }

    /**
     * Get the member.
     */
    public function getMember(): MemberModel
    {
        return $this->member;
    }

    /**
     * Set the user's membership number.
     */
    public function setLidnr(int $lidnr): void
    {
        $this->lidnr = $lidnr;
    }

    /**
     * Set the activation code.
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * Set the registration time.
     */
    public function setTime(?DateTime $time): void
    {
        $this->time = $time;
    }
}
