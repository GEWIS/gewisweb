<?php

declare(strict_types=1);

namespace Photo\Model;

use Application\Model\Traits\IdentifiableTrait;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Tag.
 *
 * @template T of TaggableInterface
 *
 * @psalm-import-type PhotoGdprArrayType from Photo as ImportedPhotoGdprArrayType
 * @psalm-type TagGdprArrayType = array{
 *     id: int,
 *     photo: ImportedPhotoGdprArrayType,
 * }
 */
#[Entity]
#[InheritanceType(value: 'SINGLE_TABLE')]
#[DiscriminatorColumn(
    name: 'type',
    type: 'string',
)]
#[DiscriminatorMap(
    value: [
        'body' => BodyTag::class,
        'member' => MemberTag::class,
    ],
)]
abstract class Tag implements ResourceInterface
{
    use IdentifiableTrait;

    #[ManyToOne(
        targetEntity: Photo::class,
        inversedBy: 'tags',
    )]
    #[JoinColumn(
        name: 'photo_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected Photo $photo;

    public function getPhoto(): Photo
    {
        return $this->photo;
    }

    public function setPhoto(Photo $photo): void
    {
        $this->photo = $photo;
    }

    /**
     * @psalm-return T
     */
    abstract public function getTagged(): TaggableInterface;

    /**
     * @psalm-param T $tagged
     */
    abstract public function setTagged(TaggableInterface $tagged): void;

    abstract public function getType(): string;

    /**
     * Returns the Tag as an associative array.
     *
     * @return array{
     *     id: int,
     *     photo_id: int,
     *     type: string,
     *     tagged_id: int,
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'photo_id' => $this->getPhoto()->getId(),
            'type' => $this->getType(),
            'tagged_id' => $this->getTagged()->getId(),
        ];
    }

    /**
     * @return TagGdprArrayType
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
        return 'tag';
    }
}
