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
use User\Model\User as UserModel;

/**
 * Vote, represents a vote for a photo of the week.
 */
#[Entity]
#[HasLifecycleCallbacks]
class Vote implements ResourceInterface
{
    /**
     * Vote ID.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * Date and time when the photo was voted for.
     */
    #[Column(type: "datetime")]
    protected DateTime $dateTime;

    /**
     * The photo which was voted for.
     */
    #[ManyToOne(
        targetEntity: Photo::class,
        inversedBy: "votes",
    )]
    #[JoinColumn(
        name: "photo_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected Photo $photo;

    /**
     * The member who voted.
     */
    #[ManyToOne(targetEntity: UserModel::class)]
    #[JoinColumn(
        name: "voter_id",
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected UserModel $voter;

    /**
     * Vote constructor.
     *
     * @param Photo $photo
     * @param UserModel $voter The member whom voted
     */
    public function __construct(Photo $photo, UserModel $voter)
    {
        $this->dateTime = new DateTime();
        $this->voter = $voter;
        $this->photo = $photo;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param Photo $photo
     */
    public function setPhoto(Photo $photo): void
    {
        $this->photo = $photo;
    }

    /**
     * @return Photo
     */
    public function getPhoto(): Photo
    {
        return $this->photo;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'vote';
    }
}
