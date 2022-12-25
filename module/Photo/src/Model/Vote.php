<?php

namespace Photo\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    JoinColumn,
    ManyToOne,
};

/**
 * Vote, represents a vote for a photo of the week.
 */
#[Entity]
class Vote
{
    use IdentifiableTrait;

    /**
     * Date and time when the photo was voted for.
     */
    #[Column(type: "datetime")]
    protected DateTime $dateTime;

    /**
     * Vote constructor.
     *
     * @param Photo $photo
     * @param MemberModel $voter The member who voted
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
        #[ManyToOne(targetEntity: MemberModel::class)]
        #[JoinColumn(
            name: "voter_id",
            referencedColumnName: "lidnr",
            nullable: false,
        )]
        protected MemberModel $voter,
    ) {
        $this->dateTime = new DateTime();
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
