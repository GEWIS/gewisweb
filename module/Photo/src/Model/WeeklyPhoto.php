<?php

declare(strict_types=1);

namespace Photo\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Override;

/**
 * Maintains a list of the "Foto of the week".
 */
#[Entity]
class WeeklyPhoto implements ResourceInterface
{
    use IdentifiableTrait;

    /**
     * The start date of the week the photo is based on.
     */
    #[Column(type: 'date')]
    protected DateTime $week;

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
    protected Photo $photo;

    /**
     * If a photo of the week is hidden, it is not shown to visitors who are NOT logged in.
     */
    #[Column(type: 'boolean')]
    protected bool $hidden = false;

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

    /**
     * Get the resource ID.
     */
    #[Override]
    public function getResourceId(): string
    {
        return 'weeklyphoto';
    }
}
