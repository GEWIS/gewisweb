<?php

declare(strict_types=1);

namespace Decision\Model\SubDecision\Board;

use DateTime;
use Decision\Model\BoardMember;
use Decision\Model\Enums\BoardFunctions;
use Decision\Model\Member;
use Decision\Model\SubDecision;
use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\AssociationOverrides;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Override;

/**
 * Installation as board member.
 */
#[Entity]
#[AssociationOverrides([
    new AssociationOverride(
        name: 'member',
        joinColumns: new JoinColumn(
            name: 'lidnr',
            referencedColumnName: 'lidnr',
            nullable: false,
        ),
    ),
])]
class Installation extends SubDecision
{
    /**
     * Function given.
     */
    #[Column(
        type: 'string',
        enumType: BoardFunctions::class,
    )]
    protected BoardFunctions $function;

    /**
     * The date at which the installation is in effect.
     */
    #[Column(type: 'date')]
    protected DateTime $date;

    /**
     * Discharge.
     */
    #[OneToOne(
        targetEntity: Discharge::class,
        mappedBy: 'installation',
    )]
    protected ?Discharge $discharge = null;

    /**
     * Release.
     */
    #[OneToOne(
        targetEntity: Release::class,
        mappedBy: 'installation',
    )]
    protected ?Release $release = null;

    /**
     * Board member reference.
     */
    #[OneToOne(
        targetEntity: BoardMember::class,
        mappedBy: 'installationDec',
    )]
    protected BoardMember $boardMember;

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
     */
    #[Override]
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
