<?php

declare(strict_types=1);

namespace App\Entity\Decision\SubDecision\Board;

use App\Entity\Decision\BoardMember;
use App\Entity\Decision\Enums\BoardFunctions;
use App\Entity\Decision\Member;
use App\Entity\Decision\SubDecision;
use App\Entity\Decision\Traits\MemberAwareTrait;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Installation as board member.
 */
#[Entity]
class Installation extends SubDecision
{
    use MemberAwareTrait;

    /**
     * Function given.
     */
    #[Column(
        type: Types::STRING,
        enumType: BoardFunctions::class,
    )]
    private BoardFunctions $function;

    /**
     * The date at which the installation is in effect.
     */
    #[Column(type: Types::DATE_MUTABLE)]
    private DateTime $date;

    /**
     * Discharge.
     */
    #[OneToOne(
        targetEntity: Discharge::class,
        mappedBy: 'installation',
    )]
    private ?Discharge $discharge = null;

    /**
     * Release.
     */
    #[OneToOne(
        targetEntity: Release::class,
        mappedBy: 'installation',
    )]
    private ?Release $release = null;

    /**
     * Board member reference.
     */
    #[OneToOne(
        targetEntity: BoardMember::class,
        mappedBy: 'installationDec',
    )]
    private BoardMember $boardMember;

    /**
     * Get the function.
     */
    public function getFunction(): BoardFunctions
    {
        return $this->function;
    }

    /**
     * Set the function.
     */
    public function setFunction(BoardFunctions $function): void
    {
        $this->function = $function;
    }

    /**
     * Get the member.
     *
     * @psalm-suppress InvalidNullableReturnType
     * @psalm-suppress NullableReturnStatement
     */
    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * Get the date.
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Set the date.
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * Get the discharge.
     */
    public function getDischarge(): ?Discharge
    {
        return $this->discharge;
    }

    /**
     * Get the release.
     */
    public function getRelease(): ?Release
    {
        return $this->release;
    }

    /**
     * Get the board member decision.
     */
    public function getBoardMember(): BoardMember
    {
        return $this->boardMember;
    }
}
