<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Decision\Organ as OrganModel;
use App\Repository\Activity\MaxActivitiesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Max Activities model.
 * Contains the max amount of activities an organ may create options for
 * Note that this is the limit per period!.
 *
 * @psalm-type MaxActivitiesArrayType = array{
 *     id: int,
 *     organ: OrganModel,
 *     value: int,
 * }
 */
#[Entity(repositoryClass: MaxActivitiesRepository::class)]
class MaxActivities
{
    use IdentifiableTrait;

    /**
     * Who created this activity.
     */
    #[ManyToOne(targetEntity: OrganModel::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    private OrganModel $organ;

    /**
     * The value of the option.
     */
    #[Column(type: Types::INTEGER)]
    private int $value;

    /**
     * The associated period.
     */
    #[ManyToOne(
        targetEntity: ActivityOptionCreationPeriod::class,
        inversedBy: 'maxActivities',
    )]
    private ActivityOptionCreationPeriod $period;

    public function getPeriod(): ActivityOptionCreationPeriod
    {
        return $this->period;
    }

    /**
     * Set the period.
     */
    public function setPeriod(ActivityOptionCreationPeriod $period): void
    {
        $this->period = $period;
    }

    public function getOrgan(): OrganModel
    {
        return $this->organ;
    }

    /**
     * Set the organ.
     */
    public function setOrgan(OrganModel $organ): void
    {
        $this->organ = $organ;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Set the value.
     */
    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return MaxActivitiesArrayType
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'organ' => $this->getOrgan(),
            'value' => $this->getValue(),
        ];
    }
}
