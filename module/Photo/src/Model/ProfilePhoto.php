<?php

declare(strict_types=1);

namespace Photo\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    JoinColumn,
    ManyToOne,
    OneToOne,
};
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * ProfilePhoto.
 */
#[Entity]
class ProfilePhoto implements ResourceInterface
{
    use IdentifiableTrait;

    #[ManyToOne(
        targetEntity: Photo::class,
        inversedBy: "profilePhotos",
    )]
    #[JoinColumn(
        name: "photo_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected Photo $photo;

    #[OneToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        name: "member_id",
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected MemberModel $member;

    /**
     * Date and time when the photo was taken.
     */
    #[Column(type: "datetime")]
    protected DateTime $dateTime;

    /**
     * Date and time when the photo was taken.
     */
    #[Column(type: "boolean")]
    protected bool $explicit;

    /**
     * @return Photo
     */
    public function getPhoto(): Photo
    {
        return $this->photo;
    }

    /**
     * @return MemberModel
     */
    public function getMember(): MemberModel
    {
        return $this->member;
    }

    /**
     * Get the date.
     *
     * @return DateTime
     */
    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    /**
     * Get the explicit bool.
     *
     * @return bool
     */
    public function isExplicit(): bool
    {
        return $this->explicit;
    }

    /**
     * @param Photo $photo
     */
    public function setPhoto(Photo $photo): void
    {
        $this->photo = $photo;
    }

    /**
     * @param MemberModel $member
     */
    public function setMember(MemberModel $member): void
    {
        $this->member = $member;
    }

    /**
     * @param DateTime $dateTime
     */
    public function setDateTime(DateTime $dateTime): void
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @param bool $explicit
     */
    public function setExplicit(bool $explicit): void
    {
        $this->explicit = $explicit;
    }

    /**
     * Get the resource Id.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'profilePhoto';
    }
}
