<?php

namespace Photo\Model;

use DateTime;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    OneToOne,
};
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Maintains a list of the "Foto of the week".
 */
#[Entity]
class WeeklyPhoto implements ResourceInterface
{
    /**
     * Week Id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     * The start date of the week the photo is based on.
     */
    #[Column(type: "date")]
    protected DateTime $week;

    /**
     * The photo of the week.
     */
    #[OneToOne(
        targetEntity: Photo::class,
        inversedBy: "weeklyPhoto",
    )]
    #[JoinColumn(
        name: "photo_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected Photo $photo;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getWeek(): DateTime
    {
        return $this->week;
    }

    /**
     * @return Photo
     */
    public function getPhoto(): Photo
    {
        return $this->photo;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @param DateTime $week
     */
    public function setWeek(DateTime $week): void
    {
        $this->week = $week;
    }

    /**
     * @param Photo $photo
     */
    public function setPhoto(Photo $photo): void
    {
        $this->photo = $photo;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'weeklyphoto';
    }
}
