<?php

declare(strict_types=1);

namespace Photo\Model;

use Application\Model\Traits\IdentifiableTrait;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use User\Permissions\Resource\OwnerResourceInterface;

/**
 * Tag.
 *
 * @psalm-import-type PhotoGdprArrayType from Photo as ImportedPhotoGdprArrayType
 * @psalm-type HiddenGdprArrayType = array{
 *     id: int,
 *     photo: ImportedPhotoGdprArrayType,
 * }
 */
#[Entity]
#[Table(name: 'HiddenPhoto')]
#[UniqueConstraint(
    name: 'hiddenPhoto_idx',
    columns: ['photo_id', 'member_id'],
)]
class HiddenPhoto implements ResourceInterface, OwnerResourceInterface
{
    use IdentifiableTrait;

    #[ManyToOne(
        targetEntity: Photo::class,
        inversedBy: 'hiddenPhotos',
    )]
    #[JoinColumn(
        name: 'photo_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected Photo $photo;

    #[ManyToOne(
        targetEntity: MemberModel::class,
        inversedBy: 'hiddenPhotos',
    )]
    #[JoinColumn(
        name: 'member_id',
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    protected MemberModel $member;

    public function getPhoto(): Photo
    {
        return $this->photo;
    }

    public function getMember(): MemberModel
    {
        return $this->member;
    }

    public function setPhoto(Photo $photo): void
    {
        $this->photo = $photo;
    }

    public function setMember(MemberModel $member): void
    {
        $this->member = $member;
    }

    /**
     * Returns the Tag as an associative array.
     *
     * @return array{
     *     id: int,
     *     photo_id: int,
     *     member_id: int,
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'photo_id' => $this->getPhoto()->getId(),
            'member_id' => $this->getMember()->getLidnr(),
        ];
    }

    /**
     * @return HiddenGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'id' => $this->getId(),
            'photo' => $this->getPhoto()->toGdprArray(),
        ];
    }

    /**
     * Get the resource ID.
     */
    public function getResourceId(): string
    {
        return 'hiddenPhoto';
    }

    /**
     * Get the resource owner.
     */
    public function getResourceOwner(): MemberModel
    {
        return $this->getMember();
    }
}
