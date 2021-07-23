<?php

namespace Photo\Model;

use DateTime;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    HasLifecycleCallbacks,
    Id,
    JoinColumn,
    ManyToOne,
};
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Hit, represents a hit for a photo.
 */
#[Entity]
#[HasLifecycleCallbacks]
class Hit implements ResourceInterface
{
    /**
     * Hit ID.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     * Date and time when the photo was viewed.
     */
    #[Column(type: "datetime")]
    protected DateTime $dateTime;

    /**
     * The photo which was viewed.
     */
    #[ManyToOne(
        targetEntity: "Photo\Model\Photo",
        inversedBy: "hits",
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
    public function getDateTime(): DateTime
    {
        return $this->dateTime;
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
     * @param DateTime $dateTime
     */
    public function setDateTime(DateTime $dateTime): void
    {
        $this->dateTime = $dateTime;
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
        return 'hit';
    }
}
