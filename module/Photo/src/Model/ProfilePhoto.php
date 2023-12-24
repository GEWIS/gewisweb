<?php

declare(strict_types=1);

namespace Photo\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use DateTimeInterface;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * ProfilePhoto.
 *
 * @psalm-import-type PhotoGdprArrayType from Photo as ImportedPhotoGdprArrayType
 * @psalm-type ProfilePhotoGdprArrayType = array{
 *     dateTime: string,
 *     explicit: bool,
 *     photo: ImportedPhotoGdprArrayType,
 * }
 */
#[Entity]
class ProfilePhoto implements ResourceInterface
{
    use IdentifiableTrait;

    #[ManyToOne(
        targetEntity: Photo::class,
        inversedBy: 'profilePhotos',
    )]
    #[JoinColumn(
        name: 'photo_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected Photo $photo;

    #[OneToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        name: 'member_id',
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    protected MemberModel $member;

    /**
     * Date and time when the photo was taken.
     */
    #[Column(type: 'datetime')]
    protected DateTime $dateTime;

    /**
     * Date and time when the photo was taken.
     */
    #[Column(type: 'boolean')]
    protected bool $explicit;

    public function getPhoto(): Photo
    {
        return $this->photo;
    }

    public function getMember(): MemberModel
    {
        return $this->member;
    }

    /**
     * Get the date.
     */
    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    /**
     * Get the explicit bool.
     */
    public function isExplicit(): bool
    {
        return $this->explicit;
    }

    public function setPhoto(Photo $photo): void
    {
        $this->photo = $photo;
    }

    public function setMember(MemberModel $member): void
    {
        $this->member = $member;
    }

    public function setDateTime(DateTime $dateTime): void
    {
        $this->dateTime = $dateTime;
    }

    public function setExplicit(bool $explicit): void
    {
        $this->explicit = $explicit;
    }

    /**
     * @return ProfilePhotoGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'dateTime' => $this->getDateTime()->format(DateTimeInterface::ATOM),
            'explicit' => $this->isExplicit(),
            'photo' => $this->getPhoto()->toGdprArray(),
        ];
    }

    /**
     * Get the resource Id.
     */
    public function getResourceId(): string
    {
        return 'profilePhoto';
    }
}
