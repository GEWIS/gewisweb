<?php

namespace Photo\Model;

use DateTime;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
};
use User\Model\User as UserModel;

/**
 * Vote, represents a vote for a photo of the week.
 */
#[Entity]
class Vote
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
     * Vote constructor.
     *
     * @param Photo $photo
     * @param UserModel $voter The member who voted
     */
    public function __construct(
        #[ManyToOne(
            targetEntity: Photo::class,
            inversedBy: "votes",
        )]
        #[JoinColumn(
            name: "photo_id",
            referencedColumnName: "id",
            nullable: false,
        )]
        protected Photo $photo,
        #[ManyToOne(targetEntity: UserModel::class)]
        #[JoinColumn(
            name: "voter_id",
            referencedColumnName: "lidnr",
            nullable: false,
        )]
        protected UserModel $voter,
    ) {
        $this->dateTime = new DateTime();
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
}
