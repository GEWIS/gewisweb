<?php

declare(strict_types=1);

namespace App\Entity\Photo;

use App\Entity\Application\Traits\IdentifiableTrait;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Maintains a list of the "Photo of the Week".
 */
#[Entity]
class WeeklyPhoto
{
    use IdentifiableTrait;

    /**
     * The start date of the week the photo is based on.
     */
    #[Column(type: Types::DATE_MUTABLE)]
    private DateTime $week;

    /**
     * The photo of the week.
     */
    #[OneToOne(
        targetEntity: Photo::class,
        inversedBy: 'weeklyPhoto',
    )]
    #[JoinColumn(
        name: 'photo_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private Photo $photo;

    /**
     * If a photo of the week is hidden, it is not shown to visitors who are NOT logged in.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $hidden = false;

    public function getWeek(): DateTime
    {
        return $this->week;
    }

    public function getPhoto(): Photo
    {
        return $this->photo;
    }

    public function setWeek(DateTime $week): void
    {
        $this->week = $week;
    }

    public function setPhoto(Photo $photo): void
    {
        $this->photo = $photo;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }
}
