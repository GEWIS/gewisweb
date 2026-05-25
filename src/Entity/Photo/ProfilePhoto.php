<?php

declare(strict_types=1);

namespace App\Entity\Photo;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Decision\Member as MemberModel;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;

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
class ProfilePhoto
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
    private Photo $photo;

    #[OneToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        name: 'member_id',
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    private MemberModel $member;

    /**
     * Date and time when the photo was taken.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $dateTime;

    /**
     * Date and time when the photo was taken.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $explicit;

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
