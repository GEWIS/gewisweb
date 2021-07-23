<?php

namespace Photo\Model;

use DateTime;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    HasLifecycleCallbacks,
    Id,
    JoinColumn,
    ManyToOne,
    OneToOne,
};
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * ProfilePhoto.
 */
#[Entity]
#[HasLifecycleCallbacks]
class ProfilePhoto implements ResourceInterface
{
    /**
     * Tag ID.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    #[ManyToOne(
        targetEntity: "Photo\Model\Photo",
        inversedBy: "tags",
    )]
    #[JoinColumn(
        name: "photo_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected Photo $photo;

    #[OneToOne(targetEntity: "Decision\Model\Member")]
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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

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
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @param Photo $photo
     */
    public function setPhoto(Photo $photo)
    {
        $this->photo = $photo;
    }

    /**
     * @param MemberModel $member
     */
    public function setMember(MemberModel $member)
    {
        $this->member = $member;
    }

    /**
     * @param DateTime $dateTime
     */
    public function setDateTime(DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @param bool $explicit
     */
    public function setExplicit(bool $explicit)
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
