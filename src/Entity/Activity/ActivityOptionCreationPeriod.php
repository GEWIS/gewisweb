<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\Activity\ActivityOptionCreationPeriodRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToMany;

/**
 * Activity Options Creation Period
 * Contains a period during which options may be created.
 *
 * @psalm-import-type MaxActivitiesArrayType from MaxActivities as ImportedMaxActivitiesArrayType
 */
#[Entity(repositoryClass: ActivityOptionCreationPeriodRepository::class)]
class ActivityOptionCreationPeriod
{
    use IdentifiableTrait;

    /**
     * The date and time the planning period starts.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $beginPlanningTime;

    /**
     * The date and time the planning period ends.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $endPlanningTime;

    /**
     * The date and time the period for which options can be created starts.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $beginOptionTime;

    /**
     * The date and time the period for which options can be created ends.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $endOptionTime;

    /**
     * The number of activities an organ can create in a period.
     *
     * @var Collection<array-key, MaxActivities>
     */
    #[OneToMany(
        targetEntity: MaxActivities::class,
        mappedBy: 'period',
        cascade: ['remove'],
        orphanRemoval: true,
    )]
    private Collection $maxActivities;

    public function __construct()
    {
        $this->maxActivities = new ArrayCollection();
    }

    public function getBeginPlanningTime(): DateTime
    {
        return $this->beginPlanningTime;
    }

    public function setBeginPlanningTime(DateTime $beginPlanningTime): void
    {
        $this->beginPlanningTime = $beginPlanningTime;
    }

    public function getEndPlanningTime(): DateTime
    {
        return $this->endPlanningTime;
    }

    public function setEndPlanningTime(DateTime $endPlanningTime): void
    {
        $this->endPlanningTime = $endPlanningTime;
    }

    public function getBeginOptionTime(): DateTime
    {
        return $this->beginOptionTime;
    }

    public function setBeginOptionTime(DateTime $beginOptionTime): void
    {
        $this->beginOptionTime = $beginOptionTime;
    }

    public function getEndOptionTime(): DateTime
    {
        return $this->endOptionTime;
    }

    public function setEndOptionTime(DateTime $endOptionTime): void
    {
        $this->endOptionTime = $endOptionTime;
    }

    /**
     * @return Collection<array-key, MaxActivities>
     */
    public function getMaxActivities(): Collection
    {
        return $this->maxActivities;
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return array{
     *     id: int,
     *     beginPlanningTime: datetime,
     *     endPlanningTime: datetime,
     *     beginOptionTime: datetime,
     *     endOptionTime: datetime,
     *     maxActivities: ImportedMaxActivitiesArrayType[],
     * }
     */
    public function toArray(): array
    {
        $maxActivitiesArrays = [];
        foreach ($this->getMaxActivities() as $maxActivity) {
            $maxActivitiesArrays[] = $maxActivity->toArray();
        }

        return [
            'id' => $this->getId(),
            'beginPlanningTime' => $this->getBeginPlanningTime(),
            'endPlanningTime' => $this->getEndPlanningTime(),
            'beginOptionTime' => $this->getBeginOptionTime(),
            'endOptionTime' => $this->getEndOptionTime(),
            'maxActivities' => $maxActivitiesArrays,
        ];
    }
}
